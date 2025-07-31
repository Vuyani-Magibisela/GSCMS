<?php
// Debug session and role
require_once __DIR__ . '/../app/bootstrap.php';

echo '<h1>Session & Role Debug</h1>';

echo '<h2>Session Data:</h2>';
echo '<pre>' . print_r($_SESSION, true) . '</pre>';

try {
    $auth = \App\Core\Auth::getInstance();
    
    echo '<h2>Auth Status:</h2>';
    echo 'Authenticated: ' . ($auth->check() ? 'YES' : 'NO') . '<br>';
    
    if ($auth->check()) {
        $user = $auth->user();
        echo 'User ID: ' . $user->id . '<br>';
        echo 'Username: ' . $user->username . '<br>';
        echo 'Role: ' . $user->role . '<br>';
        echo 'Is Admin: ' . ($user->isAdmin() ? 'YES' : 'NO') . '<br>';
        echo 'Has super_admin role: ' . ($user->hasRole('super_admin') ? 'YES' : 'NO') . '<br>';
        echo 'Has any admin roles: ' . ($user->hasAnyRole(['super_admin', 'competition_admin']) ? 'YES' : 'NO') . '<br>';
        
        echo '<h2>Role Constants:</h2>';
        echo 'SUPER_ADMIN constant: ' . \App\Models\User::SUPER_ADMIN . '<br>';
        echo 'COMPETITION_ADMIN constant: ' . \App\Models\User::COMPETITION_ADMIN . '<br>';
    } else {
        echo '<p>User not authenticated</p>';
    }
    
} catch (Exception $e) {
    echo '<h2>Error:</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<h2>Test Links:</h2>';
echo '<a href="/GSCMS/public/dev-login-admin">Dev Login</a><br>';
echo '<a href="/GSCMS/public/dashboard">Dashboard</a><br>';
echo '<a href="/GSCMS/public/admin/dashboard">Admin Dashboard (Direct)</a><br>';
?>