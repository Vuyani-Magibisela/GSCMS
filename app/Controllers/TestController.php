<?php
// app/Controllers/TestController.php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class TestController extends BaseController
{
    public function database(Request $request, Response $response)
    {
        try {
            // Test database connection
            $users = $this->db->table('users')->limit(5)->get();
            
            // Test query builder
            $userCount = $this->db->table('users')->count();
            $adminCount = $this->db->table('users')->where('role', 'super_admin')->count();
            
            return $this->json([
                'status' => 'success',
                'message' => 'Database connection working',
                'data' => [
                    'total_users' => $userCount,
                    'admin_users' => $adminCount,
                    'sample_users' => $users,
                    'database' => 'MySQL',
                    'connection' => 'PDO'
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}