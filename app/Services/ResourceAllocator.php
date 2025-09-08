<?php
// app/Services/ResourceAllocator.php

namespace App\Services;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceAllocation;
use App\Core\Database;

class ResourceAllocator
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Allocate resources for an event, team, or category
     */
    public function allocateResources($allocationType, $allocatedToId, $resources, $allocatedBy, $allocationDate, $returnDate = null)
    {
        $allocations = [];
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            foreach ($resources as $resourceData) {
                $resourceId = $resourceData['resource_id'];
                $quantity = $resourceData['quantity'];
                
                $allocation = $this->createAllocation([
                    'resource_id' => $resourceId,
                    'allocation_type' => $allocationType,
                    'allocated_to_id' => $allocatedToId,
                    'quantity' => $quantity,
                    'allocation_date' => $allocationDate,
                    'return_date' => $returnDate,
                    'allocated_by' => $allocatedBy,
                    'condition_on_allocation' => $resourceData['condition'] ?? 'good'
                ]);
                
                $allocations[] = $allocation;
            }
            
            $this->db->commit();
            
            // Send notification about allocation
            $this->sendAllocationNotification($allocations, $allocationType, $allocatedToId);
            
            return [
                'success' => true,
                'allocations' => $allocations,
                'message' => count($allocations) . ' resources allocated successfully'
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a single resource allocation
     */
    private function createAllocation($data)
    {
        $resource = new Resource();
        $resource = $resource->find($data['resource_id']);
        
        if (!$resource) {
            throw new \Exception("Resource not found: {$data['resource_id']}");
        }
        
        if (!$resource->checkAvailability($data['quantity'])) {
            throw new \Exception("Insufficient quantity for resource {$resource->resource_name}. Available: {$resource->available_quantity}, Requested: {$data['quantity']}");
        }
        
        // Reserve the resource quantity
        $resource->reserve($data['quantity']);
        
        // Create allocation record
        $allocation = $this->insertAllocation($data);
        
        // Log the allocation
        $this->logAllocation($allocation, 'allocated');
        
        return $allocation;
    }
    
    /**
     * Insert allocation record into database
     */
    private function insertAllocation($data)
    {
        $query = "
            INSERT INTO resource_allocations (
                resource_id, allocation_type, allocated_to_id, quantity, 
                allocation_date, return_date, allocated_by, condition_on_allocation, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'allocated')
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['resource_id'],
            $data['allocation_type'],
            $data['allocated_to_id'],
            $data['quantity'],
            $data['allocation_date'],
            $data['return_date'],
            $data['allocated_by'],
            $data['condition_on_allocation']
        ]);
        
        $allocationId = $this->db->lastInsertId();
        
        // Return the allocation with resource details
        $allocationQuery = "
            SELECT ra.*, r.resource_name, r.resource_code, r.unit_type,
                   rc.category_name, u.name as allocated_by_name
            FROM resource_allocations ra
            JOIN resources r ON ra.resource_id = r.id
            JOIN resource_categories rc ON r.category_id = rc.id
            LEFT JOIN users u ON ra.allocated_by = u.id
            WHERE ra.id = ?
        ";
        
        $stmt = $this->db->prepare($allocationQuery);
        $stmt->execute([$allocationId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Return allocated resources
     */
    public function returnResources($allocationIds, $returnedTo, $returnConditions = [])
    {
        $returns = [];
        
        $this->db->beginTransaction();
        
        try {
            foreach ($allocationIds as $allocationId) {
                $condition = $returnConditions[$allocationId] ?? 'good';
                $return = $this->processReturn($allocationId, $returnedTo, $condition);
                $returns[] = $return;
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'returns' => $returns,
                'message' => count($returns) . ' resources returned successfully'
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process a single return
     */
    private function processReturn($allocationId, $returnedTo, $condition)
    {
        // Get allocation details
        $allocation = $this->getAllocation($allocationId);
        
        if (!$allocation || $allocation['status'] !== 'allocated') {
            throw new \Exception("Invalid allocation or already returned: {$allocationId}");
        }
        
        // Update allocation record
        $this->updateAllocationReturn($allocationId, $returnedTo, $condition);
        
        // Update resource quantities based on condition
        $resource = new Resource();
        $resource = $resource->find($allocation['resource_id']);
        
        if ($condition === 'good') {
            // Return to available stock
            $resource->reserved_quantity -= $allocation['quantity'];
            $resource->available_quantity += $allocation['quantity'];
        } elseif ($condition === 'damaged') {
            // Mark as damaged
            $resource->reserved_quantity -= $allocation['quantity'];
            $resource->damaged_quantity += $allocation['quantity'];
        } elseif ($condition === 'lost') {
            // Remove from total stock
            $resource->reserved_quantity -= $allocation['quantity'];
            $resource->total_quantity -= $allocation['quantity'];
        }
        
        $resource->save();
        
        // Log the return
        $this->logAllocation($allocation, 'returned', $condition);
        
        return array_merge($allocation, [
            'return_condition' => $condition,
            'returned_to' => $returnedTo,
            'actual_return_date' => date('Y-m-d')
        ]);
    }
    
    /**
     * Get allocation details
     */
    private function getAllocation($allocationId)
    {
        $query = "
            SELECT ra.*, r.resource_name, r.resource_code
            FROM resource_allocations ra
            JOIN resources r ON ra.resource_id = r.id
            WHERE ra.id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$allocationId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Update allocation with return information
     */
    private function updateAllocationReturn($allocationId, $returnedTo, $condition)
    {
        $query = "
            UPDATE resource_allocations 
            SET status = 'returned', 
                actual_return_date = CURDATE(),
                condition_on_return = ?,
                returned_to = ?
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$condition, $returnedTo, $allocationId]);
    }
    
    /**
     * Get resource requirements for a category
     */
    public function getCategoryRequirements($categoryId)
    {
        $query = "
            SELECT crr.*, r.resource_name, r.resource_code, r.available_quantity,
                   rc.category_name as resource_category
            FROM category_resource_requirements crr
            JOIN resources r ON crr.resource_id = r.id
            JOIN resource_categories rc ON r.category_id = rc.id
            WHERE crr.category_id = ?
            ORDER BY crr.is_mandatory DESC, r.resource_name ASC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$categoryId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Check resource availability for requirements
     */
    public function checkAvailabilityForRequirements($categoryId, $teamCount)
    {
        $requirements = $this->getCategoryRequirements($categoryId);
        $availability = [];
        
        foreach ($requirements as $requirement) {
            $needed = $requirement['quantity_per_team'] * $teamCount;
            $available = $requirement['available_quantity'];
            
            $availability[] = [
                'resource_id' => $requirement['resource_id'],
                'resource_name' => $requirement['resource_name'],
                'resource_code' => $requirement['resource_code'],
                'needed' => $needed,
                'available' => $available,
                'sufficient' => $available >= $needed,
                'shortfall' => max(0, $needed - $available),
                'is_mandatory' => $requirement['is_mandatory']
            ];
        }
        
        return $availability;
    }
    
    /**
     * Generate allocation plan for competition category
     */
    public function generateCategoryAllocationPlan($categoryId, $teamIds)
    {
        $requirements = $this->getCategoryRequirements($categoryId);
        $plan = [
            'category_id' => $categoryId,
            'team_count' => count($teamIds),
            'allocations' => [],
            'conflicts' => [],
            'total_cost' => 0
        ];
        
        foreach ($requirements as $requirement) {
            $totalNeeded = $requirement['quantity_per_team'] * count($teamIds);
            $available = $requirement['available_quantity'];
            
            if ($available >= $totalNeeded) {
                $plan['allocations'][] = [
                    'resource_id' => $requirement['resource_id'],
                    'resource_name' => $requirement['resource_name'],
                    'quantity_per_team' => $requirement['quantity_per_team'],
                    'total_quantity' => $totalNeeded,
                    'teams' => $teamIds,
                    'cost_per_unit' => 0 // TODO: Get from resource
                ];
            } else {
                $plan['conflicts'][] = [
                    'resource_id' => $requirement['resource_id'],
                    'resource_name' => $requirement['resource_name'],
                    'needed' => $totalNeeded,
                    'available' => $available,
                    'shortfall' => $totalNeeded - $available,
                    'is_mandatory' => $requirement['is_mandatory']
                ];
            }
        }
        
        return $plan;
    }
    
    /**
     * Get allocation history for analysis
     */
    public function getAllocationHistory($filters = [])
    {
        $where = ['1=1'];
        $params = [];
        
        if (isset($filters['resource_id'])) {
            $where[] = 'ra.resource_id = ?';
            $params[] = $filters['resource_id'];
        }
        
        if (isset($filters['allocation_type'])) {
            $where[] = 'ra.allocation_type = ?';
            $params[] = $filters['allocation_type'];
        }
        
        if (isset($filters['date_from'])) {
            $where[] = 'ra.allocation_date >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $where[] = 'ra.allocation_date <= ?';
            $params[] = $filters['date_to'];
        }
        
        $query = "
            SELECT ra.*, r.resource_name, r.resource_code, r.unit_type,
                   rc.category_name, u1.name as allocated_by_name,
                   u2.name as returned_to_name
            FROM resource_allocations ra
            JOIN resources r ON ra.resource_id = r.id
            JOIN resource_categories rc ON r.category_id = rc.id
            LEFT JOIN users u1 ON ra.allocated_by = u1.id
            LEFT JOIN users u2 ON ra.returned_to = u2.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ra.created_at DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Log allocation activity
     */
    private function logAllocation($allocation, $action, $condition = null)
    {
        $message = "Resource {$allocation['resource_code']} ({$allocation['quantity']} units) {$action}";
        
        if ($condition) {
            $message .= " in {$condition} condition";
        }
        
        error_log("RESOURCE ALLOCATION: " . $message);
        
        // TODO: Implement proper logging system
    }
    
    /**
     * Send allocation notification
     */
    private function sendAllocationNotification($allocations, $allocationType, $allocatedToId)
    {
        // TODO: Implement notification system
        
        $resourceCount = count($allocations);
        $message = "Allocated {$resourceCount} resources for {$allocationType} ID {$allocatedToId}";
        
        error_log("ALLOCATION NOTIFICATION: " . $message);
    }
    
    /**
     * Get overdue allocations
     */
    public function getOverdueAllocations()
    {
        $query = "
            SELECT ra.*, r.resource_name, r.resource_code, r.unit_type,
                   rc.category_name, u.name as allocated_by_name,
                   DATEDIFF(CURDATE(), ra.return_date) as days_overdue
            FROM resource_allocations ra
            JOIN resources r ON ra.resource_id = r.id
            JOIN resource_categories rc ON r.category_id = rc.id
            LEFT JOIN users u ON ra.allocated_by = u.id
            WHERE ra.status = 'allocated'
            AND ra.return_date < CURDATE()
            ORDER BY days_overdue DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Generate resource utilization report
     */
    public function getUtilizationReport($dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?: date('Y-m-01'); // Start of current month
        $dateTo = $dateTo ?: date('Y-m-d');      // Today
        
        $query = "
            SELECT 
                r.id,
                r.resource_name,
                r.resource_code,
                r.total_quantity,
                rc.category_name,
                COUNT(ra.id) as allocation_count,
                SUM(ra.quantity) as total_allocated,
                AVG(ra.quantity) as avg_allocation,
                SUM(CASE WHEN ra.status = 'allocated' THEN ra.quantity ELSE 0 END) as currently_allocated
            FROM resources r
            JOIN resource_categories rc ON r.category_id = rc.id
            LEFT JOIN resource_allocations ra ON r.id = ra.resource_id
                AND ra.allocation_date BETWEEN ? AND ?
            WHERE r.status = 'active'
            GROUP BY r.id
            ORDER BY total_allocated DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$dateFrom, $dateTo]);
        
        return $stmt->fetchAll();
    }
}