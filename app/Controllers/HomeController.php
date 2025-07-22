<?php
// app/Controllers/HomeController.php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class HomeController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        $data = [
            'title' => 'GDE SciBOTICS Competition Management System',
            'message' => 'Welcome to the SciBOTICS CMS',
            'framework' => 'Custom MVC Framework',
            'status' => 'Active'
        ];
        
        return $this->json($data);
    }
}