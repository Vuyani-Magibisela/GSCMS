<?php
// app/Controllers/HomeController.php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class HomeController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        // If user is authenticated, redirect to dashboard
        if ($this->isAuthenticated()) {
            return $response->redirect('/dashboard');
        }
        
        $data = [
            'title' => 'GDE SciBOTICS Competition Management System',
            'message' => 'Welcome to the SciBOTICS CMS',
            'baseUrl' => $this->baseUrl(),
            'loginUrl' => $this->url('auth/login'),
            'registerUrl' => $this->url('auth/register'),
            'isActivePage' => function($pageName) { return $this->isActivePage($pageName); }
        ];
        
        return $this->view('public/home', $data);
    }
    
    public function dashboard(Request $request, Response $response)
    {
        // Require authentication
        $this->requireAuth();
        
        $user = $this->user();
        
        $data = [
            'title' => 'Dashboard - GDE SciBOTICS',
            'user' => $user,
            'welcome_message' => 'Welcome back, ' . $user->getDisplayName() . '!',
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('dashboard/index', $data);
    }
}