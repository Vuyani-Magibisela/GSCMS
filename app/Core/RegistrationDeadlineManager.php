<?php

namespace App\Core;

use App\Models\Category;

class RegistrationDeadlineManager
{
    // Registration phase constants
    const PHASE_SCHOOL_REGISTRATION = 'school_registration';
    const PHASE_TEAM_REGISTRATION = 'team_registration';
    const PHASE_MODIFICATION_PERIOD = 'modification_period';
    const PHASE_COMPETITION_LOCKED = 'competition_locked';
    
    // Registration status constants
    const STATUS_REGISTRATION_OPEN = 'registration_open';
    const STATUS_REGISTRATION_CLOSING = 'registration_closing'; // 7 days before deadline
    const STATUS_REGISTRATION_CLOSED = 'registration_closed';
    const STATUS_MODIFICATION_ONLY = 'modification_only';
    const STATUS_COMPETITION_LOCKED = 'competition_locked';
    
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get current competition phase
     */
    public function getCurrentCompetitionPhase()
    {
        $query = "
            SELECT * FROM competition_phases 
            WHERE start_date <= NOW() 
            AND end_date >= NOW() 
            AND status = 'active'
            ORDER BY start_date ASC 
            LIMIT 1
        ";
        
        $result = $this->db->query($query);
        return $result[0] ?? null;
    }
    
    /**
     * Check if registration is open for schools
     */
    public function isSchoolRegistrationOpen()
    {
        $deadline = $this->getSchoolRegistrationDeadline();
        if (!$deadline) {
            return true; // No deadline set, registration open
        }
        
        return strtotime($deadline) > time();
    }
    
    /**
     * Check if team registration is open for category
     */
    public function isTeamRegistrationOpen($categoryId = null)
    {
        $deadline = $this->getTeamRegistrationDeadline($categoryId);
        if (!$deadline) {
            return true; // No deadline set, registration open
        }
        
        return strtotime($deadline) > time();
    }
    
    /**
     * Get school registration deadline
     */
    public function getSchoolRegistrationDeadline()
    {
        $result = $this->db->query(
            "SELECT deadline_date FROM registration_deadlines 
             WHERE phase_name = ? AND deadline_type = 'final' 
             ORDER BY deadline_date DESC LIMIT 1",
            [self::PHASE_SCHOOL_REGISTRATION]
        );
        
        return $result[0]['deadline_date'] ?? null;
    }
    
    /**
     * Get team registration deadline for category
     */
    public function getTeamRegistrationDeadline($categoryId = null)
    {
        $query = "
            SELECT deadline_date FROM registration_deadlines 
            WHERE phase_name = ? AND deadline_type = 'final'
        ";
        $params = [self::PHASE_TEAM_REGISTRATION];
        
        if ($categoryId) {
            $query .= " AND (category_id = ? OR category_id IS NULL)";
            $params[] = $categoryId;
        } else {
            $query .= " AND category_id IS NULL";
        }
        
        $query .= " ORDER BY deadline_date DESC LIMIT 1";
        
        $result = $this->db->query($query, $params);
        return $result[0]['deadline_date'] ?? null;
    }
    
    /**
     * Get participant registration deadline for category
     */
    public function getParticipantRegistrationDeadline($categoryId = null)
    {
        $query = "
            SELECT deadline_date FROM registration_deadlines 
            WHERE phase_name = 'participant_registration' AND deadline_type = 'final'
        ";
        $params = [];
        
        if ($categoryId) {
            $query .= " AND (category_id = ? OR category_id IS NULL)";
            $params[] = $categoryId;
        } else {
            $query .= " AND category_id IS NULL";
        }
        
        $query .= " ORDER BY deadline_date DESC LIMIT 1";
        
        $result = $this->db->query($query, $params);
        return $result[0]['deadline_date'] ?? null;
    }
    
    /**
     * Check registration deadline for specific action
     */
    public function checkRegistrationDeadlines($categoryId, $action)
    {
        $currentPhase = $this->getCurrentCompetitionPhase();
        $now = time();
        
        switch ($action) {
            case 'CREATE_SCHOOL':
                $deadline = $this->getSchoolRegistrationDeadline();
                if ($deadline && strtotime($deadline) < $now) {
                    throw new \Exception('School registration deadline has passed');
                }
                break;
                
            case 'CREATE_TEAM':
                $deadline = $this->getTeamRegistrationDeadline($categoryId);
                if ($deadline && strtotime($deadline) < $now) {
                    throw new \Exception('Team registration deadline has passed for this category');
                }
                
                // Also check if school registration is still valid
                if (!$this->isSchoolRegistrationOpen()) {
                    throw new \Exception('School registration period has ended');
                }
                break;
                
            case 'ADD_PARTICIPANT':
                $deadline = $this->getParticipantRegistrationDeadline($categoryId);
                if ($deadline && strtotime($deadline) < $now) {
                    throw new \Exception('Participant registration deadline has passed for this category');
                }
                break;
                
            case 'MODIFY_TEAM':
                $modificationDeadline = $this->getModificationDeadline($categoryId);
                if ($modificationDeadline && strtotime($modificationDeadline) < $now) {
                    return [
                        'restricted' => true, 
                        'emergency_only' => true,
                        'message' => 'Regular modifications are no longer allowed. Emergency modifications only.'
                    ];
                }
                break;
                
            case 'EMERGENCY_MODIFICATION':
                $lockDeadline = $this->getCompetitionLockDeadline($categoryId);
                if ($lockDeadline && strtotime($lockDeadline) < $now) {
                    throw new \Exception('Competition is locked. No further changes allowed.');
                }
                break;
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Get modification deadline for category
     */
    public function getModificationDeadline($categoryId = null)
    {
        $query = "
            SELECT deadline_date FROM registration_deadlines 
            WHERE phase_name = ? AND deadline_type = 'final'
        ";
        $params = [self::PHASE_MODIFICATION_PERIOD];
        
        if ($categoryId) {
            $query .= " AND (category_id = ? OR category_id IS NULL)";
            $params[] = $categoryId;
        } else {
            $query .= " AND category_id IS NULL";
        }
        
        $query .= " ORDER BY deadline_date DESC LIMIT 1";
        
        $result = $this->db->query($query, $params);
        return $result[0]['deadline_date'] ?? null;
    }
    
    /**
     * Get competition lock deadline for category
     */
    public function getCompetitionLockDeadline($categoryId = null)
    {
        $query = "
            SELECT deadline_date FROM registration_deadlines 
            WHERE phase_name = ? AND deadline_type = 'final'
        ";
        $params = [self::PHASE_COMPETITION_LOCKED];
        
        if ($categoryId) {
            $query .= " AND (category_id = ? OR category_id IS NULL)";
            $params[] = $categoryId;
        } else {
            $query .= " AND category_id IS NULL";
        }
        
        $query .= " ORDER BY deadline_date DESC LIMIT 1";
        
        $result = $this->db->query($query, $params);
        return $result[0]['deadline_date'] ?? null;
    }
    
    /**
     * Get registration status for category
     */
    public function getRegistrationStatus($categoryId = null)
    {
        $now = time();
        
        // Check school registration
        $schoolDeadline = $this->getSchoolRegistrationDeadline();
        if ($schoolDeadline && strtotime($schoolDeadline) < $now) {
            return self::STATUS_REGISTRATION_CLOSED;
        }
        
        // Check team registration
        $teamDeadline = $this->getTeamRegistrationDeadline($categoryId);
        if ($teamDeadline) {
            $timeToDeadline = strtotime($teamDeadline) - $now;
            
            if ($timeToDeadline < 0) {
                // Check if we're in modification period
                $modificationDeadline = $this->getModificationDeadline($categoryId);
                if ($modificationDeadline && strtotime($modificationDeadline) >= $now) {
                    return self::STATUS_MODIFICATION_ONLY;
                }
                
                // Check if competition is locked
                $lockDeadline = $this->getCompetitionLockDeadline($categoryId);
                if ($lockDeadline && strtotime($lockDeadline) < $now) {
                    return self::STATUS_COMPETITION_LOCKED;
                }
                
                return self::STATUS_REGISTRATION_CLOSED;
            } elseif ($timeToDeadline <= (7 * 24 * 60 * 60)) { // 7 days
                return self::STATUS_REGISTRATION_CLOSING;
            }
        }
        
        return self::STATUS_REGISTRATION_OPEN;
    }
    
    /**
     * Get days until deadline
     */
    public function getDaysUntilDeadline($categoryId, $deadlineType = 'team')
    {
        switch ($deadlineType) {
            case 'school':
                $deadline = $this->getSchoolRegistrationDeadline();
                break;
            case 'team':
                $deadline = $this->getTeamRegistrationDeadline($categoryId);
                break;
            case 'participant':
                $deadline = $this->getParticipantRegistrationDeadline($categoryId);
                break;
            case 'modification':
                $deadline = $this->getModificationDeadline($categoryId);
                break;
            default:
                return null;
        }
        
        if (!$deadline) {
            return null;
        }
        
        $timeRemaining = strtotime($deadline) - time();
        return max(0, floor($timeRemaining / (24 * 60 * 60)));
    }
    
    /**
     * Get all deadlines for category
     */
    public function getAllDeadlines($categoryId = null)
    {
        $query = "
            SELECT * FROM registration_deadlines 
            WHERE (category_id = ? OR category_id IS NULL)
            ORDER BY deadline_date ASC
        ";
        
        return $this->db->query($query, [$categoryId]);
    }
    
    /**
     * Set registration deadline
     */
    public function setRegistrationDeadline($phase, $deadlineType, $deadlineDate, $categoryId = null)
    {
        $data = [
            'phase_name' => $phase,
            'deadline_type' => $deadlineType,
            'deadline_date' => $deadlineDate,
            'category_id' => $categoryId,
            'enforcement_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Check if deadline already exists
        $existing = $this->db->query(
            "SELECT id FROM registration_deadlines 
             WHERE phase_name = ? AND deadline_type = ? AND category_id = ?",
            [$phase, $deadlineType, $categoryId]
        );
        
        if ($existing) {
            // Update existing
            unset($data['created_at']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            return $this->db->table('registration_deadlines')
                           ->where('id', $existing[0]['id'])
                           ->update($data);
        } else {
            // Create new
            return $this->db->table('registration_deadlines')->insert($data);
        }
    }
    
    /**
     * Send deadline reminder notifications
     */
    public function sendDeadlineReminders()
    {
        $upcomingDeadlines = $this->db->query("
            SELECT rd.*, c.name as category_name 
            FROM registration_deadlines rd
            LEFT JOIN categories c ON rd.category_id = c.id
            WHERE rd.deadline_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
            AND rd.enforcement_active = 1
            AND (rd.notification_sent IS NULL OR rd.notification_sent = 0)
        ");
        
        foreach ($upcomingDeadlines as $deadline) {
            $daysUntil = floor((strtotime($deadline['deadline_date']) - time()) / (24 * 60 * 60));
            
            // Send reminders at 30, 14, 7, and 1 days before deadline
            if (in_array($daysUntil, [30, 14, 7, 1])) {
                $this->sendReminderEmail($deadline, $daysUntil);
                
                // Mark as notified
                $this->db->table('registration_deadlines')
                        ->where('id', $deadline['id'])
                        ->update(['notification_sent' => 1]);
            }
        }
    }
    
    /**
     * Send reminder email
     */
    private function sendReminderEmail($deadline, $daysUntil)
    {
        // Get all school coordinators
        $coordinators = $this->db->query("
            SELECT u.email, u.first_name, u.last_name, s.name as school_name
            FROM users u
            JOIN schools s ON u.school_id = s.id
            WHERE u.role = 'school_coordinator' 
            AND u.status = 'active'
            AND s.status = 'active'
        ");
        
        foreach ($coordinators as $coordinator) {
            $subject = "Reminder: {$deadline['phase_name']} deadline approaching";
            $message = "Dear {$coordinator['first_name']},\n\n";
            $message .= "This is a reminder that the {$deadline['phase_name']} deadline ";
            $message .= "is approaching in {$daysUntil} days.\n\n";
            $message .= "Deadline: {$deadline['deadline_date']}\n";
            
            if ($deadline['category_name']) {
                $message .= "Category: {$deadline['category_name']}\n";
            }
            
            $message .= "\nPlease ensure all registrations are completed before the deadline.\n\n";
            $message .= "Best regards,\nGDE SciBOTICS Competition Team";
            
            // Send email (implement your email sending logic here)
            mail($coordinator['email'], $subject, $message);
        }
    }
    
    /**
     * Get default deadlines for 2025 competition
     */
    public static function getDefaultDeadlines()
    {
        $currentYear = date('Y');
        
        return [
            [
                'phase_name' => self::PHASE_SCHOOL_REGISTRATION,
                'deadline_type' => 'final',
                'deadline_date' => $currentYear . '-03-15 23:59:59', // Mid March
                'category_id' => null
            ],
            [
                'phase_name' => self::PHASE_TEAM_REGISTRATION,
                'deadline_type' => 'final',
                'deadline_date' => $currentYear . '-04-30 23:59:59', // End of April
                'category_id' => null
            ],
            [
                'phase_name' => 'participant_registration',
                'deadline_type' => 'final',
                'deadline_date' => $currentYear . '-05-15 23:59:59', // Mid May
                'category_id' => null
            ],
            [
                'phase_name' => self::PHASE_MODIFICATION_PERIOD,
                'deadline_type' => 'final',
                'deadline_date' => $currentYear . '-06-01 23:59:59', // Early June
                'category_id' => null
            ],
            [
                'phase_name' => self::PHASE_COMPETITION_LOCKED,
                'deadline_type' => 'final',
                'deadline_date' => $currentYear . '-06-15 23:59:59', // Mid June
                'category_id' => null
            ]
        ];
    }
}