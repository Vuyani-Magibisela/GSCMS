<?php
// app/Core/AuditLog.php

namespace App\Core;

use Exception;

class AuditLog
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update'; 
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_SUSPEND = 'suspend';
    const ACTION_ACTIVATE = 'activate';
    
    private $db;
    private $logger;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Log an audit event
     */
    public function log($entityType, $entityId, $action, $changes = [], $metadata = [])
    {
        try {
            $auth = Auth::getInstance();
            $userId = $auth->check() ? $auth->id() : null;
            
            // Prepare audit data
            $auditData = [
                'user_id' => $userId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'changes' => json_encode($changes),
                'metadata' => json_encode($metadata),
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert audit record
            $this->db->table('audit_logs')->insert($auditData);
            
            // Log critical actions to system log as well
            if (in_array($action, [self::ACTION_DELETE, self::ACTION_SUSPEND, self::ACTION_APPROVE])) {
                $this->logger->info("Audit: {$action} {$entityType} #{$entityId}", [
                    'user_id' => $userId,
                    'changes' => $changes,
                    'metadata' => $metadata
                ]);
            }
            
        } catch (Exception $e) {
            // If audit logging fails, log to system logger
            $this->logger->error("Audit logging failed: " . $e->getMessage(), [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action
            ]);
        }
    }
    
    /**
     * Log school creation
     */
    public function logSchoolCreate($schoolId, $schoolData)
    {
        $this->log('school', $schoolId, self::ACTION_CREATE, $schoolData, [
            'school_name' => $schoolData['name'] ?? 'Unknown',
            'emis_number' => $schoolData['emis_number'] ?? null
        ]);
    }
    
    /**
     * Log school update
     */
    public function logSchoolUpdate($schoolId, $oldData, $newData)
    {
        $changes = $this->calculateChanges($oldData, $newData);
        
        $this->log('school', $schoolId, self::ACTION_UPDATE, $changes, [
            'school_name' => $newData['name'] ?? $oldData['name'] ?? 'Unknown',
            'fields_changed' => array_keys($changes)
        ]);
    }
    
    /**
     * Log school deletion
     */
    public function logSchoolDelete($schoolId, $schoolData)
    {
        $this->log('school', $schoolId, self::ACTION_DELETE, $schoolData, [
            'school_name' => $schoolData['name'] ?? 'Unknown',
            'emis_number' => $schoolData['emis_number'] ?? null,
            'reason' => 'soft_delete'
        ]);
    }
    
    /**
     * Log school status change
     */
    public function logSchoolStatusChange($schoolId, $oldStatus, $newStatus, $reason = null)
    {
        $action = match($newStatus) {
            'active' => self::ACTION_ACTIVATE,
            'suspended' => self::ACTION_SUSPEND,
            'approved' => self::ACTION_APPROVE,
            default => self::ACTION_UPDATE
        };
        
        $this->log('school', $schoolId, $action, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ], [
            'reason' => $reason,
            'status_change' => true
        ]);
    }
    
    /**
     * Get audit history for a school
     */
    public function getSchoolHistory($schoolId, $limit = 50)
    {
        try {
            return $this->db->query("
                SELECT al.*, 
                       u.first_name, 
                       u.last_name, 
                       u.email,
                       u.role
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.entity_type = 'school' AND al.entity_id = ?
                ORDER BY al.created_at DESC
                LIMIT ?
            ", [$schoolId, $limit]);
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get school audit history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent audit activity
     */
    public function getRecentActivity($entityType = null, $limit = 100)
    {
        try {
            $query = "
                SELECT al.*, 
                       u.first_name, 
                       u.last_name, 
                       u.email,
                       u.role
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
            ";
            
            $params = [];
            
            if ($entityType) {
                $query .= " WHERE al.entity_type = ?";
                $params[] = $entityType;
            }
            
            $query .= " ORDER BY al.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            return $this->db->query($query, $params);
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get recent audit activity: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate changes between old and new data
     */
    private function calculateChanges($oldData, $newData)
    {
        $changes = [];
        
        // Check for modified fields
        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP()
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Clean up old audit logs
     */
    public function cleanup($daysToKeep = 90)
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
            
            $deleted = $this->db->query("
                DELETE FROM audit_logs 
                WHERE created_at < ?
            ", [$cutoffDate]);
            
            $this->logger->info("Audit log cleanup completed", [
                'days_kept' => $daysToKeep,
                'records_deleted' => $deleted
            ]);
            
            return $deleted;
            
        } catch (Exception $e) {
            $this->logger->error("Audit log cleanup failed: " . $e->getMessage());
            return false;
        }
    }
}