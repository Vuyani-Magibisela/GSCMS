<?php
// test-websocket-connection.php - WebSocket Connection Test Script

require_once __DIR__ . '/app/bootstrap.php';

echo "SciBOTICS WebSocket Connection Test\n";
echo "===================================\n\n";

// Check if ReactPHP Socket Client is available (for testing)
if (!class_exists('React\Socket\Connector')) {
    echo "❌ ReactPHP Socket not found. Installing for testing...\n";
    echo "Run: composer require react/socket\n\n";
    
    // Fallback to basic socket test
    echo "Testing basic socket connection to localhost:8080...\n";
    
    $socket = @fsockopen('localhost', 8080, $errno, $errstr, 5);
    
    if ($socket) {
        echo "✅ Successfully connected to WebSocket server port\n";
        fclose($socket);
        
        // Test WebSocket handshake
        echo "\nTesting WebSocket handshake...\n";
        testWebSocketHandshake();
    } else {
        echo "❌ Failed to connect: $errstr ($errno)\n";
        echo "\nTroubleshooting steps:\n";
        echo "1. Make sure WebSocket server is running: ./websocket-manager.sh start\n";
        echo "2. Check if port 8080 is open: netstat -tuln | grep 8080\n";
        echo "3. Check server logs: ./websocket-manager.sh logs\n";
    }
    
    exit;
}

// Full WebSocket test with ReactPHP
echo "Running comprehensive WebSocket test...\n\n";

use React\EventLoop\Loop;
use React\Socket\Connector;
use React\Stream\WritableResourceStream;

$loop = Loop::get();
$connector = new Connector($loop);

$testResults = [
    'connection' => false,
    'handshake' => false,
    'message_send' => false,
    'message_receive' => false
];

echo "1. Testing connection to ws://localhost:8080...\n";

$connector->connect('tcp://localhost:8080')
    ->then(function ($connection) use (&$testResults, $loop) {
        echo "✅ TCP connection established\n";
        $testResults['connection'] = true;
        
        echo "2. Performing WebSocket handshake...\n";
        
        // WebSocket handshake
        $key = base64_encode(random_bytes(16));
        $handshake = "GET / HTTP/1.1\r\n" .
                    "Host: localhost:8080\r\n" .
                    "Upgrade: websocket\r\n" .
                    "Connection: Upgrade\r\n" .
                    "Sec-WebSocket-Key: $key\r\n" .
                    "Sec-WebSocket-Version: 13\r\n" .
                    "\r\n";
        
        $connection->write($handshake);
        
        $connection->on('data', function ($data) use (&$testResults, $connection, $loop, $key) {
            $response = (string) $data;
            
            if (strpos($response, 'HTTP/1.1 101') === 0) {
                echo "✅ WebSocket handshake successful\n";
                $testResults['handshake'] = true;
                
                echo "3. Testing message send...\n";
                
                // Send test message
                $testMessage = json_encode([
                    'type' => 'test',
                    'message' => 'Hello from test script',
                    'timestamp' => time()
                ]);
                
                $frame = chr(0x81) . chr(strlen($testMessage)) . $testMessage;
                $connection->write($frame);
                
                echo "✅ Test message sent\n";
                $testResults['message_send'] = true;
                
                // Set timeout for response
                $loop->addTimer(3, function() use ($connection, &$testResults) {
                    echo "4. Testing message receive...\n";
                    if (!$testResults['message_receive']) {
                        echo "⚠️  No response received within 3 seconds\n";
                    }
                    $connection->close();
                });
                
            } else {
                // Check if this is a WebSocket frame response
                if (strlen($response) >= 2) {
                    $firstByte = ord($response[0]);
                    $secondByte = ord($response[1]);
                    
                    if (($firstByte & 0x80) && ($secondByte & 0x7F)) {
                        echo "✅ Received WebSocket frame response\n";
                        $testResults['message_receive'] = true;
                    }
                }
            }
        });
        
        $connection->on('close', function() use (&$testResults) {
            echo "\nConnection closed.\n";
            echo "\n" . str_repeat("=", 40) . "\n";
            echo "TEST RESULTS:\n";
            echo "Connection: " . ($testResults['connection'] ? "✅ PASS" : "❌ FAIL") . "\n";
            echo "Handshake: " . ($testResults['handshake'] ? "✅ PASS" : "❌ FAIL") . "\n";
            echo "Message Send: " . ($testResults['message_send'] ? "✅ PASS" : "❌ FAIL") . "\n";
            echo "Message Receive: " . ($testResults['message_receive'] ? "✅ PASS" : "⚠️  TIMEOUT") . "\n";
            
            $passCount = array_sum($testResults);
            echo "\nOverall: $passCount/4 tests passed\n";
            
            if ($passCount === 4) {
                echo "🎉 All tests passed! WebSocket server is working correctly.\n";
            } elseif ($passCount >= 2) {
                echo "⚠️  Basic functionality working, but some issues detected.\n";
            } else {
                echo "❌ Major issues detected. Check server logs.\n";
            }
        });
        
    }, function ($error) {
        echo "❌ Connection failed: " . $error->getMessage() . "\n";
        echo "\nTroubleshooting:\n";
        echo "1. Start WebSocket server: ./websocket-manager.sh start\n";
        echo "2. Check server status: ./websocket-manager.sh status\n";
        echo "3. View error logs: ./websocket-manager.sh logs error\n";
    });

// Set overall timeout
$loop->addTimer(10, function() use ($loop) {
    echo "\n⏰ Test timeout reached (10 seconds)\n";
    $loop->stop();
});

$loop->run();

function testWebSocketHandshake() {
    echo "Creating WebSocket handshake test...\n";
    
    $socket = fsockopen('localhost', 8080, $errno, $errstr, 5);
    if (!$socket) {
        echo "❌ Cannot create socket connection\n";
        return false;
    }
    
    $key = base64_encode(random_bytes(16));
    $handshake = "GET / HTTP/1.1\r\n" .
                "Host: localhost:8080\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Key: $key\r\n" .
                "Sec-WebSocket-Version: 13\r\n" .
                "\r\n";
    
    fwrite($socket, $handshake);
    
    // Read response
    $response = fread($socket, 1024);
    fclose($socket);
    
    if (strpos($response, 'HTTP/1.1 101') === 0) {
        echo "✅ WebSocket handshake successful\n";
        echo "Server response headers received\n";
        return true;
    } else {
        echo "❌ WebSocket handshake failed\n";
        echo "Server response: " . substr($response, 0, 200) . "...\n";
        return false;
    }
}

?>