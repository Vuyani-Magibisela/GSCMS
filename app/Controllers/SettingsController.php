<?php
// app/Controllers/SettingsController.php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class SettingsController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        // Require authentication
        $this->requireAuth();
        
        $user = $this->auth->user();
        
        $data = [
            'title' => 'Settings - GSCMS',
            'user' => $user,
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('settings/index', $data);
    }
}