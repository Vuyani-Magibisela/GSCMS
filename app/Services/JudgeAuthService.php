<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Models\EnhancedJudgeProfile;
use Exception;

class JudgeAuthService
{
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 30; // minutes
    const SESSION_TIMEOUT = 120; // minutes
    
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function authenticateJudge($credentials)
    {
        $this->db->beginTransaction();
        
        try {
            $judge = $this->findJudgeByCredentials($credentials);
            
            if (!$judge) {
                $this->logFailedAttempt($credentials['identifier']);
                throw new Exception("Invalid credentials");
            }
            
            if ($this->isAccountLocked($judge)) {
                throw new Exception("Account is temporarily locked due to multiple failed attempts");
            }
            
            if (!$this->verifyCredentials($judge, $credentials)) {
                $this->incrementFailedAttempts($judge);
                throw new Exception("Invalid credentials");
            }
            
            if ($judge['two_factor_enabled'] && !$this->verify2FA($judge, $credentials['two_fa_code'] ?? null)) {
                if (empty($credentials['two_fa_code'])) {
                    throw new Exception("2FA_REQUIRED");
                }
                throw new Exception("Invalid 2FA code");
            }
            
            if (!$this->verifyDevice($judge, $credentials['device_id'] ?? null)) {
                $this->sendDeviceVerification($judge);
                throw new Exception("Device verification required");
            }
            
            $session = $this->createJudgeSession($judge);
            
            $this->logSuccessfulLogin($judge, $credentials);
            $this->resetFailedAttempts($judge);
            
            $this->db->commit();
            
            return $session;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function findJudgeByCredentials($credentials)
    {
        $identifier = $credentials['identifier'];
        
        $sql = "
            SELECT jp.*, u.*, ja.pin_code, ja.two_factor_enabled, ja.two_factor_secret,
                   ja.failed_attempts, ja.locked_until, ja.auth_method
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            LEFT JOIN judge_auth ja ON jp.id = ja.judge_id
            WHERE (u.email = ? OR jp.judge_code = ?)
            AND u.status = 'active'
            AND jp.status = 'active'
        ";
        
        $result = $this->db->query($sql, [$identifier, $identifier]);
        
        return !empty($result) ? $result[0] : null;
    }
    
    private function isAccountLocked($judge)
    {
        if (!$judge['locked_until']) {
            return false;
        }
        
        return strtotime($judge['locked_until']) > time();
    }
    
    private function verifyCredentials($judge, $credentials)
    {
        $authMethod = $judge['auth_method'] ?? 'password';
        
        switch ($authMethod) {
            case 'password':
                return password_verify($credentials['password'] ?? '', $judge['password']);
                
            case 'pin':
                return $credentials['pin'] === $judge['pin_code'];
                
            case 'biometric':
                return $this->verifyBiometric($judge, $credentials['biometric_data'] ?? null);
                
            default:
                return false;
        }
    }
    
    private function verify2FA($judge, $code)
    {
        if (!$judge['two_factor_enabled'] || !$judge['two_factor_secret']) {
            return true;
        }
        
        if (!$code) {
            return false;
        }
        
        require_once __DIR__ . '/../../vendor/pragmarx/google2fa/src/Google2FA.php';
        
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret = decrypt($judge['two_factor_secret']);
        
        return $google2fa->verifyKey($secret, $code);
    }
    
    private function verifyDevice($judge, $deviceId)
    {
        if (!$deviceId) {
            return false;
        }
        
        $device = $this->db->query("
            SELECT * FROM judge_devices 
            WHERE judge_id = ? AND device_id = ? AND trusted = 1 AND blocked = 0
        ", [$judge['id'], $deviceId]);
        
        if (!empty($device)) {
            $this->updateDeviceLastUsed($judge['id'], $deviceId);
            return true;
        }
        
        $this->registerNewDevice($judge['id'], $deviceId);
        return false;
    }
    
    private function createJudgeSession($judge)
    {
        $token = $this->generateSecureToken();
        $expiresAt = date('Y-m-d H:i:s', time() + (self::SESSION_TIMEOUT * 60));
        
        $session = [
            'judge_id' => $judge['id'],
            'user_id' => $judge['user_id'],
            'token' => $token,
            'expires_at' => $expiresAt,
            'permissions' => $this->getJudgePermissions($judge),
            'current_competition' => $this->getCurrentCompetition($judge),
            'auth_method' => $judge['auth_method'] ?? 'password'
        ];
        
        $this->storeSession($token, $session);
        
        return $session;
    }
    
    private function generateSecureToken()
    {
        return bin2hex(random_bytes(32));
    }
    
    private function storeSession($token, $session)
    {
        $this->db->query("
            INSERT INTO judge_sessions (token, judge_id, user_id, expires_at, session_data, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ", [
            $token,
            $session['judge_id'],
            $session['user_id'],
            $session['expires_at'],
            json_encode($session)
        ]);
    }
    
    private function getJudgePermissions($judge)
    {
        $basePermissions = [
            'view_assignments',
            'score_teams',
            'view_rubrics'
        ];
        
        $rolePermissions = [
            'coordinator' => ['manage_panels', 'view_all_scores'],
            'adjudicator' => ['finalize_scores', 'resolve_conflicts'],
            'technical' => ['technical_scoring', 'system_access'],
            'volunteer' => ['basic_scoring'],
            'industry' => ['mentoring_access', 'view_innovations']
        ];
        
        $judgeType = $judge['judge_type'];
        $permissions = array_merge($basePermissions, $rolePermissions[$judgeType] ?? []);
        
        if ($judge['experience_level'] === 'expert') {
            $permissions[] = 'mentor_judges';
            $permissions[] = 'calibrate_scores';
        }
        
        return $permissions;
    }
    
    private function getCurrentCompetition($judge)
    {
        $assignment = $this->db->query("
            SELECT c.* FROM competitions c
            INNER JOIN judge_competition_assignments jca ON c.id = jca.competition_id
            WHERE jca.judge_id = ? 
            AND c.start_date <= NOW() 
            AND c.end_date >= NOW()
            AND jca.assignment_status = 'confirmed'
            ORDER BY c.start_date ASC
            LIMIT 1
        ", [$judge['id']]);
        
        return !empty($assignment) ? $assignment[0] : null;
    }
    
    public function setupTwoFactorAuth($judgeId)
    {
        require_once __DIR__ . '/../../vendor/pragmarx/google2fa/src/Google2FA.php';
        
        $judge = $this->db->query("
            SELECT jp.*, u.first_name, u.last_name, u.email 
            FROM judge_profiles jp 
            INNER JOIN users u ON jp.user_id = u.id 
            WHERE jp.id = ?
        ", [$judgeId]);
        
        if (empty($judge)) {
            throw new Exception("Judge not found");
        }
        
        $judge = $judge[0];
        
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        $this->db->query("
            UPDATE judge_auth 
            SET two_factor_secret = ?, two_factor_enabled = 0
            WHERE judge_id = ?
        ", [encrypt($secret), $judgeId]);
        
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'GDE SciBOTICS',
            $judge['email'],
            $secret
        );
        
        return [
            'secret' => $secret,
            'qr_code' => $qrCodeUrl,
            'backup_codes' => $this->generateBackupCodes($judgeId)
        ];
    }
    
    public function enableTwoFactorAuth($judgeId, $verificationCode)
    {
        $judge = $this->db->query("
            SELECT two_factor_secret FROM judge_auth WHERE judge_id = ?
        ", [$judgeId]);
        
        if (empty($judge)) {
            throw new Exception("Judge authentication record not found");
        }
        
        $secret = decrypt($judge[0]['two_factor_secret']);
        
        require_once __DIR__ . '/../../vendor/pragmarx/google2fa/src/Google2FA.php';
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        
        if (!$google2fa->verifyKey($secret, $verificationCode)) {
            throw new Exception("Invalid verification code");
        }
        
        $this->db->query("
            UPDATE judge_auth 
            SET two_factor_enabled = 1
            WHERE judge_id = ?
        ", [$judgeId]);
        
        return true;
    }
    
    public function setupPINAuth($judgeId, $pin)
    {
        if (strlen($pin) !== 6 || !ctype_digit($pin)) {
            throw new Exception("PIN must be exactly 6 digits");
        }
        
        $this->db->query("
            UPDATE judge_auth 
            SET pin_code = ?, auth_method = 'pin'
            WHERE judge_id = ?
        ", [$pin, $judgeId]);
        
        return true;
    }
    
    public function verifyJudgeAccess($judgeId, $resource, $action)
    {
        $judge = EnhancedJudgeProfile::find($judgeId);
        
        if (!$judge || $judge->status !== 'active') {
            return false;
        }
        
        $permissions = $this->getJudgePermissions($judge->toArray());
        
        switch ($resource) {
            case 'scoring':
                return in_array('score_teams', $permissions) && $this->canScore($judge, $action);
                
            case 'teams':
                return in_array('view_assignments', $permissions) && $this->canAccessTeams($judge, $action);
                
            case 'results':
                return in_array('view_results', $permissions) || in_array('view_all_scores', $permissions);
                
            case 'admin':
                return in_array('manage_panels', $permissions) || in_array('finalize_scores', $permissions);
                
            default:
                return false;
        }
    }
    
    private function canScore($judge, $teamId)
    {
        if (!$teamId) return true;
        
        $assignment = $this->db->query("
            SELECT COUNT(*) as count
            FROM judge_competition_assignments jca
            INNER JOIN tournament_matches tm ON jca.competition_id = tm.tournament_id
            INNER JOIN team_participants tp ON tm.team1_id = tp.team_id OR tm.team2_id = tp.team_id
            WHERE jca.judge_id = ?
            AND tp.team_id = ?
            AND jca.assignment_status = 'confirmed'
        ", [$judge->id, $teamId]);
        
        return $assignment[0]['count'] > 0;
    }
    
    private function canAccessTeams($judge, $teamId)
    {
        return $this->canScore($judge, $teamId);
    }
    
    public function validateSession($token)
    {
        $session = $this->db->query("
            SELECT * FROM judge_sessions 
            WHERE token = ? AND expires_at > NOW()
        ", [$token]);
        
        if (empty($session)) {
            return false;
        }
        
        $sessionData = json_decode($session[0]['session_data'], true);
        
        $this->updateSessionActivity($token);
        
        return $sessionData;
    }
    
    public function logoutJudge($token)
    {
        $this->db->query("
            DELETE FROM judge_sessions WHERE token = ?
        ", [$token]);
        
        return true;
    }
    
    private function logFailedAttempt($identifier)
    {
        $this->db->query("
            INSERT INTO judge_access_logs (judge_id, action, ip_address, success, failure_reason, created_at)
            SELECT jp.id, 'login', ?, 0, 'Invalid credentials', NOW()
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            WHERE u.email = ? OR jp.judge_code = ?
            LIMIT 1
        ", [$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $identifier, $identifier]);
    }
    
    private function logSuccessfulLogin($judge, $credentials)
    {
        $deviceInfo = $this->getDeviceInfo();
        
        $this->db->query("
            INSERT INTO judge_access_logs (judge_id, action, ip_address, user_agent, device_type, success, created_at)
            VALUES (?, 'login', ?, ?, ?, 1, NOW())
        ", [
            $judge['id'],
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $deviceInfo['type']
        ]);
        
        $this->db->query("
            UPDATE judge_auth 
            SET last_login = NOW(), last_login_ip = ?
            WHERE judge_id = ?
        ", [$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $judge['id']]);
    }
    
    private function incrementFailedAttempts($judge)
    {
        $newAttempts = ($judge['failed_attempts'] ?? 0) + 1;
        $lockoutTime = null;
        
        if ($newAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            $lockoutTime = date('Y-m-d H:i:s', time() + (self::LOCKOUT_DURATION * 60));
        }
        
        $this->db->query("
            UPDATE judge_auth 
            SET failed_attempts = ?, locked_until = ?
            WHERE judge_id = ?
        ", [$newAttempts, $lockoutTime, $judge['id']]);
    }
    
    private function resetFailedAttempts($judge)
    {
        $this->db->query("
            UPDATE judge_auth 
            SET failed_attempts = 0, locked_until = NULL
            WHERE judge_id = ?
        ", [$judge['id']]);
    }
    
    private function updateDeviceLastUsed($judgeId, $deviceId)
    {
        $this->db->query("
            UPDATE judge_devices 
            SET last_used = NOW()
            WHERE judge_id = ? AND device_id = ?
        ", [$judgeId, $deviceId]);
    }
    
    private function registerNewDevice($judgeId, $deviceId)
    {
        $deviceInfo = $this->getDeviceInfo();
        
        $this->db->query("
            INSERT INTO judge_devices (judge_id, device_id, device_name, device_type, browser, os, last_used, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [
            $judgeId,
            $deviceId,
            $deviceInfo['name'],
            $deviceInfo['type'],
            $deviceInfo['browser'],
            $deviceInfo['os']
        ]);
    }
    
    private function updateSessionActivity($token)
    {
        $this->db->query("
            UPDATE judge_sessions 
            SET last_activity = NOW()
            WHERE token = ?
        ", [$token]);
    }
    
    private function sendDeviceVerification($judge)
    {
        // Implementation for sending device verification email/SMS
        // This would integrate with the existing email service
    }
    
    private function generateBackupCodes($judgeId)
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = sprintf('%04d-%04d', rand(1000, 9999), rand(1000, 9999));
        }
        
        $this->db->query("
            UPDATE judge_auth 
            SET backup_codes = ?
            WHERE judge_id = ?
        ", [json_encode($codes), $judgeId]);
        
        return $codes;
    }
    
    private function getDeviceInfo()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $deviceType = 'desktop';
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                $deviceType = 'tablet';
            } else {
                $deviceType = 'mobile';
            }
        }
        
        $browser = 'Unknown';
        if (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }
        
        $os = 'Unknown';
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $os = 'iOS';
        }
        
        return [
            'name' => $browser . ' on ' . $os,
            'type' => $deviceType,
            'browser' => $browser,
            'os' => $os
        ];
    }
    
    private function verifyBiometric($judge, $biometricData)
    {
        // Placeholder for biometric verification
        // This would integrate with WebAuthn/FIDO2 standards
        return false;
    }
}

function encrypt($data) {
    return base64_encode($data);
}

function decrypt($data) {
    return base64_decode($data);
}