<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\JudgeAuthService;
use App\Models\EnhancedJudgeProfile;

class JudgeAuthController extends Controller
{
    private $authService;
    
    public function __construct()
    {
        parent::__construct();
        $this->authService = new JudgeAuthService();
    }
    
    public function index()
    {
        if ($this->isJudgeAuthenticated()) {
            return $this->redirect('/judge/dashboard');
        }
        
        return $this->view('judge/auth/login');
    }
    
    public function login()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->redirect('/judge/auth');
        }
        
        $data = $this->request->all();
        
        try {
            $credentials = [
                'identifier' => $data['identifier'] ?? '',
                'password' => $data['password'] ?? '',
                'pin' => $data['pin'] ?? '',
                'two_fa_code' => $data['two_fa_code'] ?? '',
                'device_id' => $data['device_id'] ?? $this->generateDeviceId()
            ];
            
            $session = $this->authService->authenticateJudge($credentials);
            
            $_SESSION['judge_session_token'] = $session['token'];
            $_SESSION['judge_id'] = $session['judge_id'];
            $_SESSION['user_id'] = $session['user_id'];
            $_SESSION['judge_permissions'] = $session['permissions'];
            
            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Authentication successful',
                    'redirect' => '/judge/dashboard',
                    'session' => [
                        'expires_at' => $session['expires_at'],
                        'permissions' => $session['permissions']
                    ]
                ]);
            }
            
            $this->session->setFlash('success', 'Welcome back! You have been successfully logged in.');
            return $this->redirect('/judge/dashboard');
            
        } catch (\Exception $e) {
            if ($e->getMessage() === '2FA_REQUIRED') {
                return $this->json([
                    'success' => false,
                    'requires_2fa' => true,
                    'message' => 'Two-factor authentication required'
                ]);
            }
            
            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }
            
            $this->session->setFlash('error', $e->getMessage());
            return $this->redirect('/judge/auth');
        }
    }
    
    public function logout()
    {
        $token = $_SESSION['judge_session_token'] ?? null;
        
        if ($token) {
            $this->authService->logoutJudge($token);
        }
        
        unset($_SESSION['judge_session_token']);
        unset($_SESSION['judge_id']);
        unset($_SESSION['user_id']);
        unset($_SESSION['judge_permissions']);
        
        if ($this->request->isAjax()) {
            return $this->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        }
        
        $this->session->setFlash('success', 'You have been logged out successfully.');
        return $this->redirect('/judge/auth');
    }
    
    public function setup2FA()
    {
        if (!$this->isJudgeAuthenticated()) {
            return $this->redirect('/judge/auth');
        }
        
        $judgeId = $_SESSION['judge_id'];
        
        if ($this->request->getMethod() === 'POST') {
            try {
                $verificationCode = $this->request->input('verification_code');
                $this->authService->enableTwoFactorAuth($judgeId, $verificationCode);
                
                return $this->json([
                    'success' => true,
                    'message' => 'Two-factor authentication enabled successfully'
                ]);
                
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }
        }
        
        try {
            $setup = $this->authService->setupTwoFactorAuth($judgeId);
            
            $data = [
                'secret' => $setup['secret'],
                'qr_code' => $setup['qr_code'],
                'backup_codes' => $setup['backup_codes']
            ];
            
            return $this->view('judge/auth/setup-2fa', $data);
            
        } catch (\Exception $e) {
            $this->session->setFlash('error', $e->getMessage());
            return $this->redirect('/judge/dashboard');
        }
    }
    
    public function setupPIN()
    {
        if (!$this->isJudgeAuthenticated()) {
            return $this->redirect('/judge/auth');
        }
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->view('judge/auth/setup-pin');
        }
        
        try {
            $pin = $this->request->input('pin');
            $confirmPin = $this->request->input('confirm_pin');
            
            if ($pin !== $confirmPin) {
                throw new \Exception('PIN confirmation does not match');
            }
            
            $judgeId = $_SESSION['judge_id'];
            $this->authService->setupPINAuth($judgeId, $pin);
            
            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => true,
                    'message' => 'PIN authentication setup successfully'
                ]);
            }
            
            $this->session->setFlash('success', 'PIN authentication has been set up successfully.');
            return $this->redirect('/judge/profile/security');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }
            
            $this->session->setFlash('error', $e->getMessage());
            return $this->redirect('/judge/auth/setup-pin');
        }
    }
    
    public function deviceManagement()
    {
        if (!$this->isJudgeAuthenticated()) {
            return $this->redirect('/judge/auth');
        }
        
        $judgeId = $_SESSION['judge_id'];
        
        $devices = $this->db->query("
            SELECT * FROM judge_devices 
            WHERE judge_id = ? 
            ORDER BY last_used DESC
        ", [$judgeId]);
        
        $data = [
            'devices' => $devices,
            'current_device_id' => $this->getCurrentDeviceId()
        ];
        
        return $this->view('judge/auth/devices', $data);
    }
    
    public function trustDevice()
    {
        if (!$this->isJudgeAuthenticated()) {
            return $this->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        try {
            $deviceId = $this->request->input('device_id');
            $judgeId = $_SESSION['judge_id'];
            
            $this->db->query("
                UPDATE judge_devices 
                SET trusted = 1 
                WHERE judge_id = ? AND device_id = ?
            ", [$judgeId, $deviceId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Device trusted successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function blockDevice()
    {
        if (!$this->isJudgeAuthenticated()) {
            return $this->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        try {
            $deviceId = $this->request->input('device_id');
            $judgeId = $_SESSION['judge_id'];
            
            $currentDeviceId = $this->getCurrentDeviceId();
            if ($deviceId === $currentDeviceId) {
                throw new \Exception('Cannot block current device');
            }
            
            $this->db->query("
                UPDATE judge_devices 
                SET blocked = 1, trusted = 0
                WHERE judge_id = ? AND device_id = ?
            ", [$judgeId, $deviceId]);
            
            $this->db->query("
                DELETE FROM judge_sessions 
                WHERE judge_id = ? 
                AND JSON_EXTRACT(session_data, '$.device_id') = ?
            ", [$judgeId, $deviceId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Device blocked successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function verifyAccess()
    {
        $resource = $this->request->input('resource');
        $action = $this->request->input('action');
        
        if (!$this->isJudgeAuthenticated()) {
            return $this->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }
        
        $judgeId = $_SESSION['judge_id'];
        $hasAccess = $this->authService->verifyJudgeAccess($judgeId, $resource, $action);
        
        return $this->json([
            'success' => true,
            'has_access' => $hasAccess
        ]);
    }
    
    public function resetPassword()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->view('judge/auth/reset-password');
        }
        
        try {
            $identifier = $this->request->input('identifier');
            
            $judge = $this->db->query("
                SELECT jp.*, u.email, u.first_name, u.last_name
                FROM judge_profiles jp
                INNER JOIN users u ON jp.user_id = u.id
                WHERE u.email = ? OR jp.judge_code = ?
                AND u.status = 'active'
                AND jp.status = 'active'
            ", [$identifier, $identifier]);
            
            if (empty($judge)) {
                throw new \Exception('Judge not found or inactive');
            }
            
            $judge = $judge[0];
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $this->db->query("
                INSERT INTO password_resets (email, token, expires_at, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                token = VALUES(token),
                expires_at = VALUES(expires_at),
                created_at = NOW()
            ", [$judge['email'], $resetToken, $expiresAt]);
            
            // Send reset email (implementation would depend on email service)
            $this->sendPasswordResetEmail($judge, $resetToken);
            
            return $this->json([
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    private function isJudgeAuthenticated()
    {
        $token = $_SESSION['judge_session_token'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        $session = $this->authService->validateSession($token);
        return $session !== false;
    }
    
    private function generateDeviceId()
    {
        return 'dev_' . bin2hex(random_bytes(8)) . '_' . time();
    }
    
    private function getCurrentDeviceId()
    {
        return $this->request->input('device_id') ?? $this->generateDeviceId();
    }
    
    private function sendPasswordResetEmail($judge, $token)
    {
        // Implementation would use the existing email service
        // This is a placeholder for the actual implementation
    }
}