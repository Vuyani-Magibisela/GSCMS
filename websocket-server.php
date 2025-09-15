<?php
// websocket-server.php - WebSocket Server for Real-Time Scoring

require_once __DIR__ . '/app/bootstrap.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Services\WebSocketServer;

// Check if Ratchet is available (would be installed via Composer)
if (!class_exists('Ratchet\Server\IoServer')) {
    echo "Error: Ratchet WebSocket library not found.\n";
    echo "Please install Ratchet using: composer require ratchet/pawl\n";
    echo "For now, the system will work without real-time features.\n";
    exit(1);
}

// Configuration
$port = 8080;
$host = '0.0.0.0';

echo "Starting SciBOTICS Real-Time Scoring WebSocket Server...\n";
echo "Host: {$host}\n";
echo "Port: {$port}\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('-', 50) . "\n";

try {
    // Create WebSocket server
    $webSocketServer = new WebSocketServer();
    
    // Create HTTP server with WebSocket support
    $server = IoServer::factory(
        new HttpServer(
            new WsServer($webSocketServer)
        ),
        $port,
        $host
    );
    
    echo "✅ WebSocket server started successfully!\n";
    echo "📡 Listening for connections on ws://{$host}:{$port}\n";
    echo "🎯 Real-time scoring is now active\n";
    echo "🔄 Press Ctrl+C to stop the server\n";
    echo str_repeat('-', 50) . "\n";
    
    // Handle shutdown gracefully
    pcntl_signal(SIGTERM, function() {
        echo "\n🛑 Received shutdown signal, stopping server...\n";
        exit(0);
    });
    
    pcntl_signal(SIGINT, function() {
        echo "\n🛑 Received interrupt signal, stopping server...\n";
        exit(0);
    });
    
    // Start the server
    $server->run();
    
} catch (Exception $e) {
    echo "❌ Failed to start WebSocket server: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check if port {$port} is already in use\n";
    echo "2. Ensure you have sufficient permissions\n";
    echo "3. Verify Ratchet library is installed\n";
    echo "4. Check firewall settings\n";
    exit(1);
}
?>