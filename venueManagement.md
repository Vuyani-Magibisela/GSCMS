# VENUE MANAGEMENT - Detailed Execution Plan
## Overview

Based on your competition structure, the venue management system needs to handle multiple locations including the main Sci-Bono Discovery Centre (for finals) and various district venues (for semifinals). With an expected 470-500 attendees for finals and a budget of R136,993.75 for facilities, this system must efficiently manage spaces, resources, and logistics.

## 1. VENUE CAPACITY TRACKING
### 1.1 Database Schema Design
```sql
-- Main venues table
CREATE TABLE venues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venue_name VARCHAR(200) NOT NULL,
    venue_type ENUM('main', 'district', 'school', 'training') NOT NULL,
    district_id INT NULL,
    address TEXT NOT NULL,
    gps_coordinates VARCHAR(100) NULL,
    contact_person VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(100) NOT NULL,
    emergency_contact VARCHAR(20) NULL,
    total_capacity INT NOT NULL,
    parking_spaces INT DEFAULT 0,
    accessibility_features JSON NULL,
    facilities JSON NULL,
    operating_hours JSON NULL,
    cost_per_day DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(id),
    INDEX idx_type_status (venue_type, status)
);

-- Venue spaces/rooms within venues
CREATE TABLE venue_spaces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venue_id INT NOT NULL,
    space_name VARCHAR(100) NOT NULL,
    space_type ENUM('competition_hall', 'judging_room', 'catering_area', 
                    'boardroom', 'classroom', 'foyer', 'outdoor', 'storage') NOT NULL,
    floor_level VARCHAR(20) DEFAULT 'Ground',
    capacity_seated INT NOT NULL,
    capacity_standing INT NULL,
    area_sqm DECIMAL(10,2) NULL,
    competition_tables INT DEFAULT 0,
    has_av_equipment BOOLEAN DEFAULT FALSE,
    has_aircon BOOLEAN DEFAULT FALSE,
    has_wifi BOOLEAN DEFAULT TRUE,
    power_outlets INT DEFAULT 0,
    amenities JSON NULL,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    daily_rate DECIMAL(10,2) DEFAULT 0.00,
    setup_time_minutes INT DEFAULT 30,
    breakdown_time_minutes INT DEFAULT 30,
    status ENUM('available', 'booked', 'maintenance', 'setup') DEFAULT 'available',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    INDEX idx_venue_type (venue_id, space_type)
);

-- Venue bookings
CREATE TABLE venue_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venue_id INT NOT NULL,
    space_id INT NULL,
    event_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    setup_start_time TIME NULL,
    breakdown_end_time TIME NULL,
    purpose ENUM('competition', 'training', 'meeting', 'judging', 'catering') NOT NULL,
    expected_attendance INT NOT NULL,
    actual_attendance INT NULL,
    special_requirements TEXT NULL,
    catering_required BOOLEAN DEFAULT FALSE,
    av_required BOOLEAN DEFAULT FALSE,
    security_required BOOLEAN DEFAULT FALSE,
    booking_status ENUM('tentative', 'confirmed', 'cancelled', 'completed') DEFAULT 'tentative',
    booking_cost DECIMAL(10,2) DEFAULT 0.00,
    invoice_number VARCHAR(50) NULL,
    payment_status ENUM('pending', 'paid', 'waived') DEFAULT 'pending',
    booked_by INT NOT NULL,
    approved_by INT NULL,
    booking_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (space_id) REFERENCES venue_spaces(id),
    FOREIGN KEY (event_id) REFERENCES calendar_events(id),
    FOREIGN KEY (booked_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    UNIQUE KEY unique_booking (space_id, booking_date, start_time),
    INDEX idx_date_status (booking_date, booking_status)
);

-- Real-time capacity tracking
CREATE TABLE venue_capacity_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venue_id INT NOT NULL,
    space_id INT NULL,
    tracking_date DATE NOT NULL,
    time_slot TIME NOT NULL,
    total_capacity INT NOT NULL,
    current_occupancy INT DEFAULT 0,
    teams_count INT DEFAULT 0,
    judges_count INT DEFAULT 0,
    volunteers_count INT DEFAULT 0,
    spectators_count INT DEFAULT 0,
    staff_count INT DEFAULT 0,
    capacity_percentage DECIMAL(5,2) GENERATED ALWAYS AS 
        ((current_occupancy / total_capacity) * 100) STORED,
    is_overcapacity BOOLEAN GENERATED ALWAYS AS 
        (current_occupancy > total_capacity) STORED,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (space_id) REFERENCES venue_spaces(id),
    INDEX idx_tracking (venue_id, tracking_date, time_slot)
);
```
### 1.2 Sci-Bono Discovery Centre Configuration
```php
// app/Config/VenueConfig.php
class VenueConfig {
    
    // Based on budget document
    const SCIBONO_SPACES = [
        'main_competition' => [
            'name' => 'Millspace',
            'capacity' => 200,
            'tables' => 20,
            'type' => 'competition_hall'
        ],
        'judging_rooms' => [
            ['name' => 'Boardroom 1', 'capacity' => 20, 'type' => 'boardroom'],
            ['name' => 'Boardroom 2', 'capacity' => 20, 'type' => 'boardroom'],
            ['name' => 'Classroom A', 'capacity' => 30, 'type' => 'classroom'],
            ['name' => 'Classroom B', 'capacity' => 30, 'type' => 'classroom']
        ],
        'support_areas' => [
            ['name' => 'Clubhouse Foyer', 'capacity' => 100, 'type' => 'foyer'],
            ['name' => 'Shitanga', 'capacity' => 50, 'type' => 'catering_area'],
            ['name' => 'Emergency Services Area', 'capacity' => 10, 'type' => 'medical']
        ]
    ];
    
    const CAPACITY_LIMITS = [
        'finals_day' => 500,  // Based on guest list
        'training_session' => 50,
        'district_competition' => 150
    ];
    
    const BUDGET_ALLOCATIONS = [
        'facilities' => 136993.75,
        'catering' => 80000.00,
        'resources' => 108000.00,
        'marketing' => 30000.00
    ];
}
```
### 1.3 Real-Time Capacity Monitor
```php
// app/Services/CapacityMonitor.php
class CapacityMonitor {
    
    private $alertThresholds = [
        'warning' => 75,  // 75% capacity
        'critical' => 90, // 90% capacity
        'full' => 100     // 100% capacity
    ];
    
    public function trackOccupancy($venueId, $spaceId = null) {
        $booking = $this->getCurrentBooking($venueId, $spaceId);
        
        if (!$booking) {
            return ['status' => 'no_event', 'occupancy' => 0];
        }
        
        $capacity = $this->getSpaceCapacity($spaceId ?: $venueId);
        $currentOccupancy = $this->calculateCurrentOccupancy($booking);
        
        $tracking = [
            'venue_id' => $venueId,
            'space_id' => $spaceId,
            'tracking_date' => date('Y-m-d'),
            'time_slot' => date('H:i:s'),
            'total_capacity' => $capacity,
            'current_occupancy' => $currentOccupancy,
            'teams_count' => $this->countTeamsPresent($booking),
            'judges_count' => $this->countJudgesPresent($booking),
            'volunteers_count' => $this->countVolunteersPresent($booking),
            'spectators_count' => $this->countSpectatorsPresent($booking),
            'staff_count' => $this->countStaffPresent($booking)
        ];
        
        $this->saveTracking($tracking);
        $this->checkAlerts($tracking);
        
        return $this->getOccupancyStatus($tracking);
    }
    
    private function checkAlerts($tracking) {
        $percentage = ($tracking['current_occupancy'] / $tracking['total_capacity']) * 100;
        
        if ($percentage >= $this->alertThresholds['critical']) {
            $this->sendCapacityAlert('critical', $tracking);
        } elseif ($percentage >= $this->alertThresholds['warning']) {
            $this->sendCapacityAlert('warning', $tracking);
        }
        
        // Check for safety violations
        if ($tracking['current_occupancy'] > $tracking['total_capacity']) {
            $this->sendSafetyAlert($tracking);
        }
    }
    
    public function getVenueHeatmap($venueId) {
        $spaces = $this->getVenueSpaces($venueId);
        $heatmap = [];
        
        foreach ($spaces as $space) {
            $occupancy = $this->getSpaceOccupancy($space->id);
            $heatmap[] = [
                'space_id' => $space->id,
                'space_name' => $space->space_name,
                'occupancy_percentage' => $occupancy['percentage'],
                'color' => $this->getHeatmapColor($occupancy['percentage']),
                'status' => $occupancy['status']
            ];
        }
        
        return $heatmap;
    }
}
```
### 1.4 Capacity Dashboard UI
```javascript
// public/js/venue-capacity-dashboard.js
class VenueCapacityDashboard {
    constructor() {
        this.venues = [];
        this.refreshInterval = 30000; // 30 seconds
        this.charts = {};
    }
    
    init() {
        this.loadVenues();
        this.initRealTimeUpdates();
        this.initCapacityCharts();
    }
    
    initCapacityCharts() {
        // Main capacity gauge
        this.charts.mainGauge = new Chart(document.getElementById('capacity-gauge'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [0, 100],
                    backgroundColor: ['#28a745', '#e9ecef'],
                    borderWidth: 0
                }]
            },
            options: {
                circumference: 180,
                rotation: 270,
                cutout: '75%',
                plugins: {
                    tooltip: { enabled: false },
                    datalabels: {
                        display: true,
                        formatter: (value, ctx) => {
                            if (ctx.dataIndex === 0) {
                                return value + '%';
                            }
                            return '';
                        }
                    }
                }
            }
        });
        
        // Space utilization heatmap
        this.initHeatmap();
    }
    
    initHeatmap() {
        const container = document.getElementById('venue-heatmap');
        
        // Create interactive floor plan
        this.heatmap = new VenueHeatmap(container, {
            floors: ['Ground', 'First', 'Second'],
            spaces: this.getSpaceLayout(),
            onClick: (spaceId) => this.showSpaceDetails(spaceId),
            onHover: (spaceId) => this.showSpaceTooltip(spaceId)
        });
    }
    
    updateCapacityDisplay(data) {
        // Update main gauge
        const percentage = (data.current_occupancy / data.total_capacity) * 100;
        this.charts.mainGauge.data.datasets[0].data = [percentage, 100 - percentage];
        
        // Update color based on threshold
        let color = '#28a745'; // Green
        if (percentage >= 90) color = '#dc3545'; // Red
        else if (percentage >= 75) color = '#ffc107'; // Yellow
        
        this.charts.mainGauge.data.datasets[0].backgroundColor[0] = color;
        this.charts.mainGauge.update();
        
        // Update statistics
        $('#current-occupancy').text(data.current_occupancy);
        $('#total-capacity').text(data.total_capacity);
        $('#teams-present').text(data.teams_count);
        $('#judges-present').text(data.judges_count);
        
        // Update status indicator
        this.updateStatusIndicator(percentage);
    }
    
    initRealTimeUpdates() {
        // WebSocket connection for real-time updates
        this.socket = new WebSocket('ws://localhost:8080/venue-capacity');
        
        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.updateCapacityDisplay(data);
            this.updateHeatmap(data.heatmap);
        };
        
        // Fallback to polling if WebSocket fails
        setInterval(() => {
            if (this.socket.readyState !== WebSocket.OPEN) {
                this.fetchCapacityData();
            }
        }, this.refreshInterval);
    }
}
```
---

## 2. RESOURCE MANAGEMENT
### 2.1 Resource Database Schema
```sql
-- Resource categories
CREATE TABLE resource_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_type ENUM('equipment', 'furniture', 'technology', 'supplies', 'safety') NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Resources inventory
CREATE TABLE resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    resource_name VARCHAR(200) NOT NULL,
    resource_code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    unit_type ENUM('piece', 'set', 'kit', 'box', 'roll') DEFAULT 'piece',
    total_quantity INT NOT NULL DEFAULT 0,
    available_quantity INT NOT NULL DEFAULT 0,
    reserved_quantity INT DEFAULT 0,
    damaged_quantity INT DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    replacement_cost DECIMAL(10,2) DEFAULT 0.00,
    supplier_name VARCHAR(200) NULL,
    supplier_contact VARCHAR(100) NULL,
    purchase_date DATE NULL,
    warranty_expiry DATE NULL,
    storage_location VARCHAR(100) NULL,
    min_quantity INT DEFAULT 1,
    reorder_point INT DEFAULT 5,
    image_path VARCHAR(255) NULL,
    specifications JSON NULL,
    status ENUM('active', 'discontinued', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES resource_categories(id),
    INDEX idx_available (available_quantity, status)
);

-- Resource allocations
CREATE TABLE resource_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resource_id INT NOT NULL,
    allocation_type ENUM('venue', 'event', 'team', 'category') NOT NULL,
    allocated_to_id INT NOT NULL,
    quantity INT NOT NULL,
    allocation_date DATE NOT NULL,
    return_date DATE NULL,
    actual_return_date DATE NULL,
    allocated_by INT NOT NULL,
    returned_to INT NULL,
    condition_on_allocation ENUM('new', 'good', 'fair', 'poor') DEFAULT 'good',
    condition_on_return ENUM('good', 'damaged', 'lost', 'not_returned') NULL,
    damage_notes TEXT NULL,
    replacement_charge DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('allocated', 'returned', 'overdue', 'lost') DEFAULT 'allocated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resource_id) REFERENCES resources(id),
    FOREIGN KEY (allocated_by) REFERENCES users(id),
    FOREIGN KEY (returned_to) REFERENCES users(id),
    INDEX idx_status_date (status, allocation_date)
);

-- Resource requirements per category
CREATE TABLE category_resource_requirements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    resource_id INT NOT NULL,
    quantity_per_team INT NOT NULL DEFAULT 1,
    is_mandatory BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (resource_id) REFERENCES resources(id),
    UNIQUE KEY unique_requirement (category_id, resource_id)
);
```
### 2.2 Competition Resources Configuration
```php
// app/Config/CompetitionResources.php
class CompetitionResources {
    
    // Based on budget document - R108,000 for mats
    const COMPETITION_MATS = [
        'total_budget' => 108000,
        'mat_specifications' => [
            'size' => '2.4m x 1.2m',
            'material' => 'EVA foam',
            'thickness' => '20mm',
            'quantity_needed' => 40,
            'cost_per_mat' => 2700
        ]
    ];
    
    const CATEGORY_REQUIREMENTS = [
        'JUNIOR' => [
            'equipment' => [
                ['name' => 'Cubroid Kit', 'quantity' => 1],
                ['name' => 'BEE Bot', 'quantity' => 1],
                ['name' => 'Competition Mat', 'quantity' => 1],
                ['name' => 'Mission Models', 'quantity' => 1]
            ]
        ],
        'SPIKE' => [
            'equipment' => [
                ['name' => 'LEGO Spike Prime Set', 'quantity' => 1],
                ['name' => 'Competition Mat', 'quantity' => 1],
                ['name' => 'Mission Models', 'quantity' => 1],
                ['name' => 'Laptop/Tablet', 'quantity' => 1]
            ]
        ],
        'ARDUINO' => [
            'equipment' => [
                ['name' => 'SciBOT Kit', 'quantity' => 1],
                ['name' => 'Arduino Robot', 'quantity' => 1],
                ['name' => 'Competition Mat', 'quantity' => 1],
                ['name' => 'Programming Device', 'quantity' => 1]
            ]
        ],
        'INVENTOR' => [
            'equipment' => [
                ['name' => 'Arduino Inventor Kit', 'quantity' => 1],
                ['name' => 'Project Materials', 'quantity' => 1],
                ['name' => 'Display Board', 'quantity' => 1],
                ['name' => 'Presentation Equipment', 'quantity' => 1]
            ]
        ]
    ];
    
    const VENUE_REQUIREMENTS = [
        'competition_hall' => [
            ['name' => 'Competition Table', 'quantity' => 20],
            ['name' => 'Competition Mat', 'quantity' => 20],
            ['name' => 'Chair', 'quantity' => 120],
            ['name' => 'Power Extension', 'quantity' => 20],
            ['name' => 'Timer Display', 'quantity' => 5]
        ],
        'judging_room' => [
            ['name' => 'Judge Table', 'quantity' => 3],
            ['name' => 'Chair', 'quantity' => 10],
            ['name' => 'Scoring Tablet', 'quantity' => 3],
            ['name' => 'Printer', 'quantity' => 1]
        ]
    ];
}
```
### 2.3 Resource Allocation Service
```php
// app/Services/ResourceAllocator.php
class ResourceAllocator {
    
    public function allocateEventResources($eventId) {
        $event = $this->getEvent($eventId);
        $requirements = $this->calculateRequirements($event);
        
        $allocations = [];
        $shortages = [];
        
        foreach ($requirements as $resource => $quantity) {
            $available = $this->checkAvailability($resource, $event->date);
            
            if ($available >= $quantity) {
                $allocations[] = $this->createAllocation($resource, $quantity, $event);
            } else {
                $shortages[] = [
                    'resource' => $resource,
                    'required' => $quantity,
                    'available' => $available,
                    'shortage' => $quantity - $available
                ];
            }
        }
        
        if (!empty($shortages)) {
            $this->handleShortages($shortages, $event);
        }
        
        return [
            'allocated' => $allocations,
            'shortages' => $shortages
        ];
    }
    
    private function calculateRequirements($event) {
        $requirements = [];
        
        // Get venue requirements
        $venue = $event->venue;
        $venueReqs = CompetitionResources::VENUE_REQUIREMENTS[$venue->type] ?? [];
        
        foreach ($venueReqs as $req) {
            $requirements[$req['name']] = $req['quantity'];
        }
        
        // Get category-specific requirements
        $teams = $this->getEventTeams($event->id);
        $categoryCount = [];
        
        foreach ($teams as $team) {
            $categoryCount[$team->category]++;
        }
        
        foreach ($categoryCount as $category => $count) {
            $catReqs = CompetitionResources::CATEGORY_REQUIREMENTS[$category] ?? [];
            foreach ($catReqs['equipment'] as $req) {
                $requirements[$req['name']] = 
                    ($requirements[$req['name']] ?? 0) + ($req['quantity'] * $count);
            }
        }
        
        return $requirements;
    }
    
    public function optimizeDistribution($phaseId) {
        $events = $this->getPhaseEvents($phaseId);
        $allResources = $this->getAllResources();
        
        // Use linear programming to optimize distribution
        $optimizer = new ResourceOptimizer();
        
        foreach ($events as $event) {
            $optimizer->addEvent($event, $this->calculateRequirements($event));
        }
        
        foreach ($allResources as $resource) {
            $optimizer->addResource($resource->name, $resource->available_quantity);
        }
        
        // Minimize transportation costs while meeting all requirements
        $solution = $optimizer->solve([
            'objective' => 'minimize_transport',
            'constraints' => [
                'meet_all_requirements' => true,
                'max_budget' => CompetitionResources::COMPETITION_MATS['total_budget']
            ]
        ]);
        
        return $solution;
    }
}
```
---

## 3. EQUIPMENT INVENTORY SYSTEM
### 3.1 Equipment Tracking Database

```sql
-- Equipment types and specifications
CREATE TABLE equipment_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    category ENUM('robotics', 'computing', 'av', 'furniture', 'safety') NOT NULL,
    brand VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    specifications JSON NULL,
    manual_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Equipment inventory
CREATE TABLE equipment_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_type_id INT NOT NULL,
    serial_number VARCHAR(100) UNIQUE NULL,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    purchase_date DATE NULL,
    purchase_cost DECIMAL(10,2) NULL,
    current_value DECIMAL(10,2) NULL,
    condition_status ENUM('new', 'excellent', 'good', 'fair', 'poor', 'broken') DEFAULT 'good',
    location_type ENUM('venue', 'storage', 'allocated', 'maintenance', 'lost') DEFAULT 'storage',
    current_location_id INT NULL,
    last_maintenance_date DATE NULL,
    next_maintenance_date DATE NULL,
    warranty_expiry DATE NULL,
    disposal_date DATE NULL,
    qr_code VARCHAR(100) UNIQUE NULL,
    rfid_tag VARCHAR(100) UNIQUE NULL,
    notes TEXT NULL,
    status ENUM('available', 'in_use', 'maintenance', 'retired') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id),
    INDEX idx_status_location (status, location_type)
);

-- Equipment check-in/check-out log
CREATE TABLE equipment_movement_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    movement_type ENUM('check_out', 'check_in', 'transfer', 'maintenance', 'repair') NOT NULL,
    from_location VARCHAR(100) NULL,
    to_location VARCHAR(100) NULL,
    responsible_person INT NOT NULL,
    purpose TEXT NULL,
    condition_before ENUM('excellent', 'good', 'fair', 'poor', 'broken') NULL,
    condition_after ENUM('excellent', 'good', 'fair', 'poor', 'broken') NULL,
    damage_report TEXT NULL,
    expected_return_date DATE NULL,
    actual_return_date DATE NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    FOREIGN KEY (equipment_id) REFERENCES equipment_inventory(id),
    FOREIGN KEY (responsible_person) REFERENCES users(id),
    INDEX idx_equipment_date (equipment_id, movement_date)
);

-- Maintenance schedules
CREATE TABLE equipment_maintenance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    maintenance_type ENUM('preventive', 'corrective', 'emergency', 'calibration') NOT NULL,
    scheduled_date DATE NOT NULL,
    completed_date DATE NULL,
    performed_by VARCHAR(100) NULL,
    service_provider VARCHAR(200) NULL,
    cost DECIMAL(10,2) DEFAULT 0.00,
    parts_replaced TEXT NULL,
    work_performed TEXT NULL,
    next_maintenance_due DATE NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'overdue') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment_inventory(id),
    INDEX idx_status_date (status, scheduled_date)
);
```
### 3.2 Equipment Management Service
```php
// app/Services/EquipmentManager.php
class EquipmentManager {
    
    private $qrGenerator;
    private $rfidReader;
    
    public function trackEquipment($assetTag) {
        $equipment = $this->getEquipmentByTag($assetTag);
        
        if (!$equipment) {
            throw new EquipmentNotFoundException("Equipment not found: {$assetTag}");
        }
        
        return [
            'equipment' => $equipment,
            'current_location' => $this->getCurrentLocation($equipment),
            'movement_history' => $this->getMovementHistory($equipment->id),
            'maintenance_status' => $this->getMaintenanceStatus($equipment->id),
            'availability' => $this->checkAvailability($equipment->id)
        ];
    }
    
    public function checkOutEquipment($equipmentId, $userId, $purpose, $returnDate) {
        DB::beginTransaction();
        
        try {
            $equipment = $this->getEquipment($equipmentId);
            
            if ($equipment->status !== 'available') {
                throw new EquipmentUnavailableException("Equipment is not available");
            }
            
            // Create movement log
            $movement = [
                'equipment_id' => $equipmentId,
                'movement_type' => 'check_out',
                'from_location' => $equipment->current_location,
                'to_location' => "User: {$userId}",
                'responsible_person' => $userId,
                'purpose' => $purpose,
                'condition_before' => $equipment->condition_status,
                'expected_return_date' => $returnDate
            ];
            
            $this->createMovementLog($movement);
            
            // Update equipment status
            $equipment->status = 'in_use';
            $equipment->location_type = 'allocated';
            $equipment->current_location_id = $userId;
            $equipment->save();
            
            // Send notification
            $this->notifyCheckOut($equipment, $userId, $returnDate);
            
            DB::commit();
            
            return $movement;
            
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    public function performMaintenance($equipmentId, $maintenanceData) {
        $equipment = $this->getEquipment($equipmentId);
        
        // Create maintenance record
        $maintenance = [
            'equipment_id' => $equipmentId,
            'maintenance_type' => $maintenanceData['type'],
            'scheduled_date' => $maintenanceData['date'],
            'performed_by' => $maintenanceData['technician'],
            'service_provider' => $maintenanceData['provider'],
            'cost' => $maintenanceData['cost'],
            'work_performed' => $maintenanceData['work'],
            'parts_replaced' => $maintenanceData['parts'],
            'status' => 'in_progress'
        ];
        
        $maintenanceId = $this->createMaintenanceRecord($maintenance);
        
        // Update equipment status
        $equipment->status = 'maintenance';
        $equipment->location_type = 'maintenance';
        $equipment->save();
        
        return $maintenanceId;
    }
    
    public function generateInventoryReport($filters = []) {
        $report = [
            'total_equipment' => $this->getTotalEquipment(),
            'by_category' => $this->getEquipmentByCategory(),
            'by_condition' => $this->getEquipmentByCondition(),
            'maintenance_due' => $this->getMaintenanceDue(),
            'value_summary' => $this->calculateTotalValue(),
            'utilization_rate' => $this->calculateUtilizationRate()
        ];
        
        if (isset($filters['category'])) {
            $report['category_detail'] = $this->getCategoryDetail($filters['category']);
        }
        
        return $report;
    }
}
```
### 3.3 Equipment Tracking Interface
```javascript
// public/js/equipment-tracker.js
class EquipmentTracker {
    constructor() {
        this.scanner = null;
        this.inventory = [];
        this.movements = [];
    }
    
    initQRScanner() {
        const video = document.getElementById('qr-scanner');
        
        this.scanner = new Instascan.Scanner({ 
            video: video,
            scanPeriod: 5,
            mirror: false
        });
        
        this.scanner.addListener('scan', (content) => {
            this.handleScan(content);
        });
        
        Instascan.Camera.getCameras().then(cameras => {
            if (cameras.length > 0) {
                this.scanner.start(cameras[0]);
            }
        });
    }
    
    handleScan(assetTag) {
        // Lookup equipment
        $.ajax({
            url: `/api/equipment/track/${assetTag}`,
            success: (data) => {
                this.displayEquipmentInfo(data);
                this.showActionOptions(data.equipment);
            },
            error: () => {
                toastr.error('Equipment not found');
            }
        });
    }
    
    displayEquipmentInfo(data) {
        const template = `
            <div class="equipment-card">
                <div class="equipment-header">
                    <h4>${data.equipment.name}</h4>
                    <span class="badge badge-${this.getStatusColor(data.equipment.status)}">
                        ${data.equipment.status}
                    </span>
                </div>
                <div class="equipment-details">
                    <p><strong>Asset Tag:</strong> ${data.equipment.asset_tag}</p>
                    <p><strong>Serial:</strong> ${data.equipment.serial_number}</p>
                    <p><strong>Location:</strong> ${data.current_location}</p>
                    <p><strong>Condition:</strong> ${data.equipment.condition_status}</p>
                </div>
                <div class="equipment-actions">
                    <button class="btn btn-primary" onclick="tracker.checkOut('${data.equipment.id}')">
                        Check Out
                    </button>
                    <button class="btn btn-success" onclick="tracker.checkIn('${data.equipment.id}')">
                        Check In
                    </button>
                    <button class="btn btn-warning" onclick="tracker.reportIssue('${data.equipment.id}')">
                        Report Issue
                    </button>
                </div>
            </div>
        `;
        
        $('#equipment-display').html(template);
    }
    
    checkOut(equipmentId) {
        $('#checkOutModal').modal('show');
        $('#checkout-equipment-id').val(equipmentId);
    }
    
    submitCheckOut() {
        const data = {
            equipment_id: $('#checkout-equipment-id').val(),
            purpose: $('#checkout-purpose').val(),
            return_date: $('#checkout-return-date').val()
        };
        
        $.ajax({
            url: '/api/equipment/checkout',
            method: 'POST',
            data: data,
            success: (response) => {
                toastr.success('Equipment checked out successfully');
                $('#checkOutModal').modal('hide');
                this.refreshInventory();
            }
        });
    }
    
    generateQRCodes() {
        $.ajax({
            url: '/api/equipment/generate-qr',
            method: 'POST',
            success: (response) => {
                // Download QR codes PDF
                window.open(response.pdf_url, '_blank');
                toastr.success(`Generated ${response.count} QR codes`);
            }
        });
    }
}
```
---

## 4. SETUP AND BREAKDOWN SCHEDULING
### 4.1 Setup/Breakdown Database Schema
```sql
-- Setup and breakdown schedules
CREATE TABLE setup_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    venue_id INT NOT NULL,
    schedule_type ENUM('setup', 'breakdown') NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_start_time TIME NOT NULL,
    scheduled_end_time TIME NOT NULL,
    actual_start_time TIME NULL,
    actual_end_time TIME NULL,
    team_leader INT NOT NULL,
    team_size INT NOT NULL DEFAULT 1,
    estimated_hours DECIMAL(4,2) NOT NULL,
    actual_hours DECIMAL(4,2) NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'delayed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES calendar_events(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (team_leader) REFERENCES users(id),
    INDEX idx_date_type (scheduled_date, schedule_type)
);

-- Setup/breakdown tasks
CREATE TABLE setup_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    task_name VARCHAR(200) NOT NULL,
    task_category ENUM('furniture', 'technology', 'signage', 'catering', 
                       'registration', 'competition_area', 'safety') NOT NULL,
    description TEXT NULL,
    assigned_to INT NULL,
    estimated_duration_minutes INT NOT NULL,
    actual_duration_minutes INT NULL,
    dependencies JSON NULL,
    priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'blocked') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES setup_schedules(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    INDEX idx_schedule_status (schedule_id, status)
);

-- Setup crew assignments
CREATE TABLE setup_crew (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('leader', 'member', 'specialist', 'volunteer') DEFAULT 'member',
    check_in_time TIME NULL,
    check_out_time TIME NULL,
    hours_worked DECIMAL(4,2) NULL,
    tasks_completed INT DEFAULT 0,
    performance_rating INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES setup_schedules(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_assignment (schedule_id, user_id)
);

-- Setup checklists
CREATE TABLE setup_checklists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venue_id INT NOT NULL,
    space_id INT NULL,
    checklist_type ENUM('pre_setup', 'setup', 'breakdown', 'post_breakdown') NOT NULL,
    checklist_name VARCHAR(200) NOT NULL,
    items JSON NOT NULL,
    is_template BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (space_id) REFERENCES venue_spaces(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```
### 4.2 Setup Coordination Service
```php
// app/Services/SetupCoordinator.php
class SetupCoordinator {
    
    const SETUP_TIMINGS = [
        'small_event' => ['setup' => 2, 'breakdown' => 1],  // hours
        'medium_event' => ['setup' => 4, 'breakdown' => 2],
        'large_event' => ['setup' => 8, 'breakdown' => 4],
        'finals' => ['setup' => 16, 'breakdown' => 6]
    ];
    
    public function createSetupSchedule($eventId) {
        $event = $this->getEvent($eventId);
        $venue = $event->venue;
        $eventSize = $this->determineEventSize($event);
        
        // Calculate setup time
        $setupHours = self::SETUP_TIMINGS[$eventSize]['setup'];
        $breakdownHours = self::SETUP_TIMINGS[$eventSize]['breakdown'];
        
        // Create setup schedule
        $setup = [
            'event_id' => $eventId,
            'venue_id' => $venue->id,
            'schedule_type' => 'setup',
            'scheduled_date' => date('Y-m-d', strtotime('-1 day', strtotime($event->date))),
            'scheduled_start_time' => $this->calculateSetupStart($event, $setupHours),
            'scheduled_end_time' => '18:00:00',
            'team_size' => $this->calculateCrewSize($eventSize),
            'estimated_hours' => $setupHours
        ];
        
        $setupId = $this->createSchedule($setup);
        
        // Create breakdown schedule
        $breakdown = [
            'event_id' => $eventId,
            'venue_id' => $venue->id,
            'schedule_type' => 'breakdown',
            'scheduled_date' => $event->date,
            'scheduled_start_time' => $event->end_time,
            'scheduled_end_time' => $this->calculateBreakdownEnd($event, $breakdownHours),
            'team_size' => $this->calculateCrewSize($eventSize) / 2,
            'estimated_hours' => $breakdownHours
        ];
        
        $breakdownId = $this->createSchedule($breakdown);
        
        // Generate task lists
        $this->generateSetupTasks($setupId, $venue, $event);
        $this->generateBreakdownTasks($breakdownId, $venue, $event);
        
        return ['setup' => $setupId, 'breakdown' => $breakdownId];
    }
    
    private function generateSetupTasks($scheduleId, $venue, $event) {
        $tasks = [];
        
        // Venue preparation tasks
        $tasks[] = [
            'task_name' => 'Clean and prepare venue',
            'task_category' => 'furniture',
            'estimated_duration_minutes' => 60,
            'priority' => 'high'
        ];
        
        // Furniture setup
        $tasks[] = [
            'task_name' => 'Set up competition tables',
            'task_category' => 'furniture',
            'estimated_duration_minutes' => 120,
            'priority' => 'critical'
        ];
        
        // Technology setup
        $tasks[] = [
            'task_name' => 'Set up scoring system',
            'task_category' => 'technology',
            'estimated_duration_minutes' => 90,
            'priority' => 'critical',
            'dependencies' => ['tables_setup', 'power_setup']
        ];
        
        // Competition mats
        $tasks[] = [
            'task_name' => 'Lay out competition mats',
            'task_category' => 'competition_area',
            'estimated_duration_minutes' => 180,
            'priority' => 'critical'
        ];
        
        // Safety setup
        $tasks[] = [
            'task_name' => 'Set up emergency services area',
            'task_category' => 'safety',
            'estimated_duration_minutes' => 45,
            'priority' => 'high'
        ];
        
        foreach ($tasks as $task) {
            $task['schedule_id'] = $scheduleId;
            $this->createTask($task);
        }
    }
    
    public function trackProgress($scheduleId) {
        $schedule = $this->getSchedule($scheduleId);
        $tasks = $this->getScheduleTasks($scheduleId);
        
        $progress = [
            'total_tasks' => count($tasks),
            'completed_tasks' => 0,
            'in_progress_tasks' => 0,
            'pending_tasks' => 0,
            'blocked_tasks' => 0,
            'completion_percentage' => 0,
            'estimated_remaining_time' => 0
        ];
        
        foreach ($tasks as $task) {
            $progress[strtolower($task->status) . '_tasks']++;
            
            if ($task->status !== 'completed') {
                $progress['estimated_remaining_time'] += $task->estimated_duration_minutes;
            }
        }
        
        $progress['completion_percentage'] = 
            ($progress['completed_tasks'] / $progress['total_tasks']) * 100;
        
        return $progress;
    }
}
```
### 4.3 Setup Management Dashboard
```javascript
// public/js/setup-dashboard.js
class SetupDashboard {
    constructor() {
        this.scheduleId = null;
        this.tasks = [];
        this.crew = [];
        this.refreshInterval = 60000; // 1 minute
    }
    
    init(scheduleId) {
        this.scheduleId = scheduleId;
        this.loadSchedule();
        this.loadTasks();
        this.loadCrew();
        this.initGanttChart();
        this.startAutoRefresh();
    }
    
    initGanttChart() {
        const tasks = this.tasks.map(task => ({
            id: task.id,
            text: task.task_name,
            start_date: task.start_time,
            duration: task.estimated_duration_minutes / 60,
            progress: task.status === 'completed' ? 1 : 0,
            priority: task.priority,
            parent: task.dependencies ? task.dependencies[0] : 0
        }));
        
        gantt.init("gantt-chart");
        gantt.parse({
            data: tasks,
            links: this.generateDependencies()
        });
        
        // Custom task colors
        gantt.templates.task_class = (start, end, task) => {
            return `priority-${task.priority}`;
        };
    }
    
    updateTaskStatus(taskId, status) {
        $.ajax({
            url: `/api/setup/task/${taskId}/status`,
            method: 'PUT',
            data: { status: status },
            success: (response) => {
                this.updateTaskDisplay(taskId, status);
                this.updateProgressBar();
                
                if (status === 'completed') {
                    this.checkDependentTasks(taskId);
                }
            }
        });
    }
    
    assignCrewMember(taskId, userId) {
        $.ajax({
            url: `/api/setup/task/${taskId}/assign`,
            method: 'POST',
            data: { user_id: userId },
            success: (response) => {
                toastr.success('Crew member assigned');
                this.refreshTaskList();
            }
        });
    }
    
    generateChecklist() {
        $.ajax({
            url: `/api/setup/schedule/${this.scheduleId}/checklist`,
            success: (checklist) => {
                const modal = $('#checklistModal');
                const body = modal.find('.modal-body');
                
                let html = '<div class="checklist">';
                checklist.items.forEach(item => {
                    html += `
                        <div class="checklist-item">
                            <input type="checkbox" id="check-${item.id}" 
                                ${item.completed ? 'checked' : ''}>
                            <label for="check-${item.id}">${item.description}</label>
                        </div>
                    `;
                });
                html += '</div>';
                
                body.html(html);
                modal.modal('show');
            }
        });
    }
    
    trackCrewHours() {
        const crewTable = $('#crew-tracking');
        
        this.crew.forEach(member => {
            const row = $(`
                <tr>
                    <td>${member.name}</td>
                    <td>${member.role}</td>
                    <td>
                        <button class="btn btn-sm btn-success check-in" 
                                data-user="${member.user_id}">
                            Check In
                        </button>
                    </td>
                    <td class="check-in-time">-</td>
                    <td class="check-out-time">-</td>
                    <td class="hours-worked">0</td>
                </tr>
            `);
            crewTable.append(row);
        });
        
        $('.check-in').on('click', function() {
            const userId = $(this).data('user');
            const now = new Date().toLocaleTimeString();
            
            $(this).closest('tr').find('.check-in-time').text(now);
            $(this).removeClass('btn-success check-in')
                   .addClass('btn-danger check-out')
                   .text('Check Out');
            
            // Log check-in
            $.ajax({
                url: '/api/setup/crew/checkin',
                method: 'POST',
                data: { user_id: userId, schedule_id: this.scheduleId }
            });
        });
    }
}
```
---
# IMPLEMENTATION TIMELINE

## Venue Infrastructure

- [ ] Create venue and space tables
- [ ] Set up Sci-Bono venue configuration
- [ ] Build capacity tracking system
- [ ] Implement booking management

## Resource Management

- [ ] Create resource inventory tables
- [ ] Build allocation system
- [ ] Implement shortage detection
- [ ] Create resource dashboard

## Equipment System

- [ ] Set up equipment database
- [ ] Build check-in/check-out system
- [ ] Implement QR/RFID tracking
- [ ] Create maintenance scheduling

## Setup Coordination

- [ ] Create setup/breakdown tables
- [ ] Build task management system
- [ ] Implement crew scheduling
- [ ] Create progress tracking

## Integration & Testing

- [ ] Integrate all venue systems
- [ ] Test capacity monitoring
- [ ] Validate resource allocation
- [ ] Complete setup workflows

---

# KEY DELIVERABLES
## 1. Venue Capacity System

- Real-time occupancy tracking
- Multi-space management
- Safety compliance monitoring
- Visual heatmaps and dashboards

## 2. Resource Optimization

- Automated allocation engine
- Shortage prediction system
- Budget tracking (R534,333.75 total)
- Distribution optimization

## 3. Equipment Tracking

- QR/RFID asset management
- Check-in/check-out system
- Maintenance scheduling
- Inventory reporting

## 4. Setup Coordination

- Task management system
- Crew scheduling and tracking
- Progress monitoring
- Checklist generation

---

#SUCCESS METRICS

| Matric | Target | Measurement |
| ---- | ---- | --- |
| Venue Utilization | ```>85%``` | Occupancy tracking |
| Resource Availability | ```>95%``` | Allocation success rate |
| Equipment Uptime | ```>98%``` | Maintenance logs |
| Setup Completion | 100% on time | Schedule tracking |
| Budget Compliance | Within R534,333.75 | Financial reports |
| Safety Incidents | 0 | Incident reports |

---
# RISK MITIGATION
| Risk | Impact | Mitigation Strategy |
| --- | --- | ---|
| Venue capacity exceeded | High | Real-time monitoring, overflow planning |
| Equipment shortage | High | Early detection, rental backup options |
| Setup delays | Medium | Buffer time, experienced crew leaders |
| Resource damage | Medium | Insurance, replacement inventory |
| Budget overrun | High | Continuous tracking, approval workflows |

This comprehensive venue management system ensures efficient utilization of the Sci-Bono Discovery Centre facilities and district venues while maintaining strict budget control and safety standards.


