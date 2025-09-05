<?php
// app/Models/User.php

namespace App\Models;

use App\Core\Validator;
use Exception;

class User extends BaseModel
{
    protected $table = 'users';
    protected $fillable = [
        'username', 'email', 'password_hash', 'first_name', 'last_name', 
        'phone', 'role', 'status', 'school_id', 'email_verified', 'reset_token', 
        'reset_token_expires', 'remember_token', 'last_login'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $softDeletes = true;
    
    // User role constants
    const SUPER_ADMIN = 'super_admin';
    const COMPETITION_ADMIN = 'competition_admin';
    const SCHOOL_COORDINATOR = 'school_coordinator';
    const TEAM_COACH = 'team_coach';
    const JUDGE = 'judge';
    const PARTICIPANT = 'participant';
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';
    
    // Relationships
    protected $belongsTo = [
        'school' => ['model' => School::class, 'foreign_key' => 'school_id']
    ];
    
    /**
     * Get school relationship (for coordinators)
     */
    public function school()
    {
        return $this->belongsTo('App\Models\School', 'school_id');
    }
    
    /**
     * Check if user is a school coordinator
     */
    public function isSchoolCoordinator()
    {
        return $this->role === self::SCHOOL_COORDINATOR;
    }
    
    /**
     * Check if user is a team coach
     */
    public function isTeamCoach()
    {
        return $this->role === self::TEAM_COACH;
    }
    
    /**
     * Get teams where user is a coach
     */
    public function coachedTeams()
    {
        return $this->db->query("
            SELECT t.* 
            FROM teams t 
            WHERE (t.coach1_id = ? OR t.coach2_id = ?)
            AND t.deleted_at IS NULL
        ", [$this->id, $this->id]);
    }
    
    /**
     * Get all available user roles
     */
    public static function getRoles()
    {
        return [
            self::SUPER_ADMIN => 'Super Administrator',
            self::COMPETITION_ADMIN => 'Competition Administrator',
            self::SCHOOL_COORDINATOR => 'School Coordinator',
            self::TEAM_COACH => 'Team Coach',
            self::JUDGE => 'Judge',
            self::PARTICIPANT => 'Participant'
        ];
    }
    
    /**
     * Get all available statuses
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_PENDING => 'Pending'
        ];
    }
    
    /**
     * Hash password using PHP's password_hash
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password against hash
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }
    
    /**
     * Set password with automatic hashing
     */
    public function setPassword($password)
    {
        $this->password_hash = self::hashPassword($password);
    }
    
    /**
     * Generate secure reset token
     */
    public function generateResetToken()
    {
        $this->reset_token = bin2hex(random_bytes(32));
        $this->reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        return $this->reset_token;
    }
    
    /**
     * Generate remember token
     */
    public function generateRememberToken()
    {
        $this->remember_token = bin2hex(random_bytes(32));
        return $this->remember_token;
    }
    
    /**
     * Clear reset token
     */
    public function clearResetToken()
    {
        $this->reset_token = null;
        $this->reset_token_expires = null;
    }
    
    /**
     * Check if reset token is valid
     */
    public function isResetTokenValid($token)
    {
        return $this->reset_token === $token && 
               $this->reset_token_expires && 
               strtotime($this->reset_token_expires) > time();
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin()
    {
        $this->last_login = date('Y-m-d H:i:s');
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        if ($this->role === self::SUPER_ADMIN) {
            return true; // Super admin has all permissions
        }
        
        return $this->role === $role;
    }
    
    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    /**
     * Check if user can login
     */
    public function canLogin()
    {
        return $this->isActive() && $this->email_verified;
    }
    
    /**
     * Get full name
     */
    public function getFullName()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
    
    /**
     * Get display name (full name or username)
     */
    public function getDisplayName()
    {
        $fullName = $this->getFullName();
        return !empty($fullName) ? $fullName : $this->username;
    }
    
    /**
     * Get role display name
     */
    public function getRoleDisplayName()
    {
        $roles = self::getRoles();
        return $roles[$this->role] ?? $this->role;
    }
    
    /**
     * Check if user is admin (super admin or competition admin)
     */
    public function isAdmin()
    {
        return $this->hasAnyRole([self::SUPER_ADMIN, self::COMPETITION_ADMIN]);
    }
    
    /**
     * Validation rules for user creation
     */
    public static function getValidationRules($isUpdate = false, $userId = null)
    {
        $rules = [
            'username' => [
                'required' => true,
                'min_length' => 3,
                'max_length' => 50,
                'regex' => '/^[a-zA-Z0-9_]+$/',
                'unique' => ['table' => 'users', 'column' => 'username', 'except' => $userId]
            ],
            'email' => [
                'required' => true,
                'email' => true,
                'max_length' => 255,
                'unique' => ['table' => 'users', 'column' => 'email', 'except' => $userId]
            ],
            'first_name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 100,
                'regex' => '/^[a-zA-Z\s\'-]+$/'
            ],
            'last_name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 100,
                'regex' => '/^[a-zA-Z\s\'-]+$/'
            ],
            'phone' => [
                'max_length' => 20,
                'regex' => '/^[\+]?[0-9\s\-\(\)]+$/'
            ],
            'role' => [
                'required' => true,
                'in' => array_keys(self::getRoles())
            ],
            'status' => [
                'in' => array_keys(self::getStatuses())
            ]
        ];
        
        if (!$isUpdate) {
            $rules['password'] = [
                'required' => true,
                'min_length' => 8,
                'regex' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
            ];
            $rules['password_confirmation'] = [
                'required' => true,
                'matches' => 'password'
            ];
        }
        
        return $rules;
    }
    
    /**
     * Validate user data
     */
    public static function validateUserData($data, $isUpdate = false, $userId = null)
    {
        $validator = new Validator();
        $rules = self::getValidationRules($isUpdate, $userId);
        
        return $validator->validate($data, $rules);
    }
    
    /**
     * Create user with validation
     */
    public static function createUser($data)
    {
        $validation = self::validateUserData($data);
        
        if (!$validation['valid']) {
            $errorMessages = [];
            foreach ($validation['errors'] as $field => $fieldErrors) {
                if (is_array($fieldErrors)) {
                    $errorMessages = array_merge($errorMessages, $fieldErrors);
                } else {
                    $errorMessages[] = $fieldErrors;
                }
            }
            throw new Exception('Validation failed: ' . implode(', ', $errorMessages));
        }
        
        // Hash password
        $data['password_hash'] = self::hashPassword($data['password']);
        unset($data['password'], $data['password_confirmation']);
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = self::STATUS_PENDING;
        }
        
        $user = new self();
        return $user->create($data);
    }
    
    /**
     * Update user with validation
     */
    public function updateUser($data)
    {
        $validation = self::validateUserData($data, true, $this->id);
        
        if (!$validation['valid']) {
            $errorMessages = [];
            foreach ($validation['errors'] as $field => $fieldErrors) {
                if (is_array($fieldErrors)) {
                    $errorMessages = array_merge($errorMessages, $fieldErrors);
                } else {
                    $errorMessages[] = $fieldErrors;
                }
            }
            throw new Exception('Validation failed: ' . implode(', ', $errorMessages));
        }
        
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = self::hashPassword($data['password']);
        }
        unset($data['password'], $data['password_confirmation']);
        
        return $this->update($this->id, $data);
    }
    
    /**
     * Find user by email
     */
    public static function findByEmail($email)
    {
        $user = new self();
        $result = $user->db->table($user->table)->where('email', $email)->first();
        return $result ? $user->newInstance($result) : null;
    }
    
    /**
     * Find user by username
     */
    public static function findByUsername($username)
    {
        $user = new self();
        $result = $user->db->table($user->table)->where('username', $username)->first();
        return $result ? $user->newInstance($result) : null;
    }
    
    /**
     * Find user by reset token
     */
    public static function findByResetToken($token)
    {
        $user = new self();
        $result = $user->db->table($user->table)
                    ->where('reset_token', $token)
                    ->where('reset_token_expires', '>', date('Y-m-d H:i:s'))
                    ->first();
        return $result ? $user->newInstance($result) : null;
    }
    
    /**
     * Find user by remember token
     */
    public static function findByRememberToken($token)
    {
        $user = new self();
        $result = $user->db->table($user->table)->where('remember_token', $token)->first();
        return $result ? $user->newInstance($result) : null;
    }
    
    /**
     * Relationship: User has many schools (as coordinator)
     */
    public function schools()
    {
        return $this->hasMany('App\\Models\\School', 'coordinator_id');
    }
    
    /**
     * Relationship: User belongs to school (if school coordinator)
     */
    public function coordinatedSchool()
    {
        return $this->hasOne('App\\Models\\School', 'coordinator_id');
    }
    
    /**
     * Scope: Only active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: Users by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }
    
    /**
     * Scope: Users by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope: Verified users
     */
    public function scopeVerified($query)
    {
        return $query->where('email_verified', 1);
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission)
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->hasPermission($permission);
    }
    
    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission($permissions)
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->hasAnyPermission($permissions);
    }
    
    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions($permissions)
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->hasAllPermissions($permissions);
    }
    
    /**
     * Check if user can access resource based on role hierarchy
     */
    public function canAccess($requiredRole)
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->canAccess($requiredRole);
    }
    
    /**
     * Check if user can manage another role
     */
    public function canManage($targetRole)
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->canManage($targetRole);
    }
    
    /**
     * Check if user owns specific resource
     */
    public function ownsResource($resourceType, $resourceId)
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->ownsResource($resourceType, $resourceId);
    }
    
    /**
     * Get all permissions for this user
     */
    public function getPermissions()
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->getPermissions();
    }
    
    /**
     * Get role hierarchy level
     */
    public function getRoleLevel()
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->getRoleLevel($this->role);
    }
    
    /**
     * Get all roles this user can manage
     */
    public function getManageableRoles()
    {
        $auth = \App\Core\Auth::getInstance();
        return $auth->getManageableRoles();
    }
    
    /**
     * Check if user is higher than or equal to given role in hierarchy
     */
    public function isHigherOrEqualTo($role)
    {
        $roleHierarchy = [
            self::PARTICIPANT => 1,
            self::TEAM_COACH => 2,
            self::JUDGE => 3,
            self::SCHOOL_COORDINATOR => 4, 
            self::COMPETITION_ADMIN => 5,
            self::SUPER_ADMIN => 6
        ];
        
        $userLevel = $roleHierarchy[$this->role] ?? 0;
        $targetLevel = $roleHierarchy[$role] ?? 999;
        
        return $userLevel >= $targetLevel;
    }
    
    /**
     * Check if user is specifically a super admin
     */
    public function isSuperAdmin()
    {
        return $this->role === self::SUPER_ADMIN;
    }
    
    /**
     * Check if user is competition admin
     */
    public function isCompetitionAdmin()
    {
        return $this->role === self::COMPETITION_ADMIN;
    }
    
    /**
     * Check if user is judge
     */
    public function isJudge()
    {
        return $this->role === self::JUDGE;
    }
    
    /**
     * Check if user is participant
     */
    public function isParticipant()
    {
        return $this->role === self::PARTICIPANT;
    }
    
    /**
     * Get users by school
     */
    public function getBySchool($schoolId)
    {
        $results = $this->db->table($this->table)
            ->where('school_id', $schoolId)
            ->where('status', self::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->orderBy('role')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        
        return $this->collection($results);
    }
}