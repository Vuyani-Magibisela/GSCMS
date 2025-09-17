<?php
require_once 'app/bootstrap.php';

// Create test data for school management system
try {
    $db = App\Core\Database::getInstance();
    
    echo "Setting up test data...\n";
    
    // Create admin user
    $adminExists = $db->table('users')->where('email', 'admin@gscms.local')->first();
    $adminByUsername = $db->table('users')->where('username', 'admin')->first();
    
    if (!$adminExists && !$adminByUsername) {
        $userId = $db->table('users')->insert([
            'username' => 'admin',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@gscms.local',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        echo "✓ Admin user created - Email: admin@gscms.local, Password: password\n";
    } else {
        echo "✓ Admin user already exists\n";
    }
    
    // Check if districts table exists
    $tables = $db->query("SHOW TABLES LIKE 'districts'");
    if (empty($tables)) {
        // Create districts table manually
        $db->execute("
            CREATE TABLE districts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                province VARCHAR(50) NOT NULL,
                code VARCHAR(10) UNIQUE,
                region VARCHAR(50),
                coordinator_id INT,
                description TEXT,
                boundary_coordinates TEXT,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            )
        ");
        echo "✓ Districts table created\n";
    }
    
    // Create sample districts
    $districtCount = $db->table('districts')->count();
    if ($districtCount == 0) {
        $sampleDistricts = [
            ['name' => 'Johannesburg East', 'province' => 'Gauteng', 'code' => 'JHB-E', 'status' => 'active'],
            ['name' => 'Johannesburg West', 'province' => 'Gauteng', 'code' => 'JHB-W', 'status' => 'active'],
            ['name' => 'Tshwane North', 'province' => 'Gauteng', 'code' => 'TSH-N', 'status' => 'active'],
            ['name' => 'Tshwane South', 'province' => 'Gauteng', 'code' => 'TSH-S', 'status' => 'active'],
            ['name' => 'Ekurhuleni North', 'province' => 'Gauteng', 'code' => 'EKU-N', 'status' => 'active'],
        ];
        
        foreach ($sampleDistricts as $district) {
            $district['created_at'] = date('Y-m-d H:i:s');
            $district['updated_at'] = date('Y-m-d H:i:s');
            $db->table('districts')->insert($district);
        }
        echo "✓ Sample districts created\n";
    }
    
    // Check if contacts table exists
    $tables = $db->query("SHOW TABLES LIKE 'contacts'");
    if (empty($tables)) {
        // Create contacts table manually
        $db->execute("
            CREATE TABLE contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                school_id INT NOT NULL,
                contact_type ENUM('principal', 'coordinator', 'deputy', 'administrative', 'it_coordinator', 'security', 'facilities', 'medical', 'other') NOT NULL,
                title VARCHAR(10),
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                position VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                mobile VARCHAR(20),
                fax VARCHAR(20),
                address TEXT,
                is_primary BOOLEAN DEFAULT FALSE,
                is_emergency BOOLEAN DEFAULT FALSE,
                language_preference ENUM('english', 'afrikaans', 'zulu', 'xhosa', 'sotho', 'tswana', 'pedi', 'venda', 'tsonga', 'ndebele', 'swati') DEFAULT 'english',
                communication_preference ENUM('email', 'phone', 'sms', 'whatsapp') DEFAULT 'email',
                status ENUM('active', 'inactive') DEFAULT 'active',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            )
        ");
        echo "✓ Contacts table created\n";
    }
    
    // Update schools table with new fields if they don't exist
    $columns = $db->query("SHOW COLUMNS FROM schools LIKE 'district_id'");
    if (empty($columns)) {
        echo "Adding new fields to schools table...\n";
        
        $alterQueries = [
            "ALTER TABLE schools ADD COLUMN emis_number VARCHAR(20) AFTER name",
            "ALTER TABLE schools ADD COLUMN school_type ENUM('primary', 'secondary', 'combined', 'special') DEFAULT 'combined' AFTER emis_number",
            "ALTER TABLE schools ADD COLUMN quintile INT AFTER school_type",
            "ALTER TABLE schools ADD COLUMN district_id INT AFTER quintile",
            "ALTER TABLE schools ADD COLUMN address_line1 VARCHAR(200) AFTER district_id",
            "ALTER TABLE schools ADD COLUMN address_line2 VARCHAR(200) AFTER address_line1",
            "ALTER TABLE schools ADD COLUMN city VARCHAR(100) AFTER address_line2",
            "ALTER TABLE schools ADD COLUMN website VARCHAR(255) AFTER email",
            "ALTER TABLE schools ADD COLUMN principal_phone VARCHAR(20) AFTER principal_email",
            "ALTER TABLE schools ADD COLUMN coordinator_id INT AFTER principal_phone",
            "ALTER TABLE schools ADD COLUMN establishment_date DATE AFTER coordinator_id",
            "ALTER TABLE schools ADD COLUMN facilities TEXT AFTER total_learners",
            "ALTER TABLE schools ADD COLUMN computer_lab TEXT AFTER facilities",
            "ALTER TABLE schools ADD COLUMN internet_status VARCHAR(100) AFTER computer_lab",
            "ALTER TABLE schools ADD COLUMN accessibility_features TEXT AFTER internet_status",
            "ALTER TABLE schools ADD COLUMN previous_participation TEXT AFTER accessibility_features",
            "ALTER TABLE schools ADD COLUMN communication_preference ENUM('email', 'phone', 'sms', 'postal') DEFAULT 'email' AFTER previous_participation",
            "ALTER TABLE schools ADD COLUMN logo_path VARCHAR(255) AFTER communication_preference",
            "ALTER TABLE schools ADD COLUMN approval_date DATETIME AFTER registration_date",
            "ALTER TABLE schools ADD COLUMN notes TEXT AFTER approval_date"
        ];
        
        foreach ($alterQueries as $query) {
            try {
                $db->execute($query);
            } catch (Exception $e) {
                // Column might already exist, continue
            }
        }
        
        // Update status enum
        try {
            $db->execute("ALTER TABLE schools MODIFY COLUMN status ENUM('pending', 'active', 'inactive', 'suspended', 'archived') DEFAULT 'pending'");
        } catch (Exception $e) {
            // Status might already be updated
        }
        
        echo "✓ Schools table enhanced\n";
    }
    
    // Create a sample school if none exist
    $schoolCount = $db->table('schools')->count();
    if ($schoolCount == 0) {
        $districtId = $db->table('districts')->where('code', 'JHB-E')->value('id');
        
        $schoolId = $db->table('schools')->insert([
            'name' => 'Sample High School',
            'emis_number' => '900123456',
            'registration_number' => '900123456789',
            'school_type' => 'secondary',
            'quintile' => 3,
            'district_id' => $districtId,
            'district' => 'Johannesburg East',
            'province' => 'Gauteng',
            'address_line1' => '123 Education Street',
            'city' => 'Johannesburg',
            'postal_code' => '2001',
            'phone' => '011-123-4567',
            'email' => 'info@samplehigh.edu.za',
            'principal_name' => 'Dr. Jane Smith',
            'principal_email' => 'principal@samplehigh.edu.za',
            'principal_phone' => '011-123-4568',
            'total_learners' => 850,
            'facilities' => 'Science laboratories, Computer lab, Library, Sports facilities',
            'computer_lab' => '30 desktop computers, high-speed internet, interactive whiteboard',
            'internet_status' => 'broadband',
            'status' => 'active',
            'registration_date' => date('Y-m-d'),
            'approval_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        echo "✓ Sample school created\n";
        
        // Create contacts for the sample school
        $contacts = [
            [
                'school_id' => $schoolId,
                'contact_type' => 'principal',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'position' => 'Principal',
                'email' => 'principal@samplehigh.edu.za',
                'phone' => '011-123-4568',
                'is_primary' => 1,
                'status' => 'active'
            ],
            [
                'school_id' => $schoolId,
                'contact_type' => 'coordinator',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'position' => 'SciBOTICS Coordinator',
                'email' => 'coordinator@samplehigh.edu.za',
                'phone' => '011-123-4569',
                'is_primary' => 0,
                'status' => 'active'
            ]
        ];
        
        foreach ($contacts as $contact) {
            $contact['created_at'] = date('Y-m-d H:i:s');
            $contact['updated_at'] = date('Y-m-d H:i:s');
            $db->table('contacts')->insert($contact);
        }
        echo "✓ Sample contacts created\n";
    }
    
    echo "\n🎉 Test data setup complete!\n\n";
    echo "You can now access the system at: http://localhost:8000\n";
    echo "Login with: admin@gscms.local / password\n\n";
    echo "Available URLs to test:\n";
    echo "• School Management: http://localhost:8000/admin/schools\n";
    echo "• District Management: http://localhost:8000/admin/districts\n";
    echo "• Create New School: http://localhost:8000/admin/schools/create\n";
    echo "• View Sample School: http://localhost:8000/admin/schools/1\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>