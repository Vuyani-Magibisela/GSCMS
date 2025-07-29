<?php
// app/Controllers/ProfileController.php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use Exception;

class ProfileController extends BaseController
{
    public function show(Request $request, Response $response)
    {
        // Require authentication
        $this->requireAuth();
        
        $user = $this->auth->user();
        
        $data = [
            'title' => 'Profile - GSCMS',
            'user' => $user,
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('profile/show', $data);
    }
    
    public function update(Request $request, Response $response)
    {
        // Require authentication
        $this->requireAuth();
        
        $user = $this->auth->user();
        
        try {
            // Validate input
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20'
            ];
            
            $data = $this->validate($rules);
            
            // Check if email is already taken by another user
            if ($data['email'] !== $user->email) {
                $existingUser = \App\Models\User::findByEmail($data['email']);
                if ($existingUser && $existingUser->id !== $user->id) {
                    $this->session->flash('error', 'Email address is already in use.');
                    return $this->redirect('/profile');
                }
            }
            
            // Update user data
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'] ?? null;
            $user->updated_at = date('Y-m-d H:i:s');
            
            $user->save();
            
            $this->session->flash('success', 'Profile updated successfully.');
            return $this->redirect('/profile');
            
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $this->session->flash('error', 'Failed to update profile. Please try again.');
            return $this->redirect('/profile');
        }
    }
}