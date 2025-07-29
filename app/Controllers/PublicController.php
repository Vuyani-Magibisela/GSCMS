<?php
// app/Controllers/PublicController.php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class PublicController extends BaseController
{
    public function about(Request $request, Response $response)
    {
        $data = [
            'title' => 'About - GDE SciBOTICS',
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('public/about', $data);
    }
    
    public function categories(Request $request, Response $response)
    {
        $data = [
            'title' => 'Competition Categories - GDE SciBOTICS',
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('public/categories', $data);
    }
    
    public function schedule(Request $request, Response $response)
    {
        $data = [
            'title' => 'Competition Schedule - GDE SciBOTICS',
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('public/schedule', $data);
    }
    
    public function leaderboard(Request $request, Response $response)
    {
        $data = [
            'title' => 'Leaderboard - GDE SciBOTICS',
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('public/leaderboard', $data);
    }
    
    public function announcements(Request $request, Response $response)
    {
        $data = [
            'title' => 'Announcements - GDE SciBOTICS',
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('public/announcements', $data);
    }
    
    public function resources(Request $request, Response $response)
    {
        $data = [
            'title' => 'Resources - GDE SciBOTICS',
            'baseUrl' => $this->baseUrl()
        ];
        
        return $this->view('public/resources', $data);
    }
}