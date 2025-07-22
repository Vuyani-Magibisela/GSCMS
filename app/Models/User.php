<?php
// app/Models/User.php

namespace App\Models;

class User extends BaseModel
{
    protected $table = 'users';
    protected $fillable = [
        'username', 'email', 'password_hash', 'first_name', 'last_name', 
        'phone', 'role', 'status', 'email_verified'
    ];
    
    /**
     * Relationship: User has many schools (as coordinator)
     */
    public function schools()
    {
        return $this->hasMany('App\\Models\\School', 'coordinator_id');
    }
    
    /**
     * Relationship: User has many teams (as coach)
     */
    public function coachedTeams()
    {
        return $this->hasMany('App\\Models\\Team', 'coach1_id');
    }
    
    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        return $this->role === $role || $this->role === 'super_admin';
    }
    
    /**
     * Check if user is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }
    
    /**
     * Get full name
     */
    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }
    
    /**
     * Scope: Only active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    /**
     * Scope: Users by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }
}