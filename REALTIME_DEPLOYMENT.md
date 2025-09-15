# Real-Time Scoring System Deployment Guide

This guide covers the deployment and configuration of the SciBOTICS real-time scoring and judging system.

## Prerequisites

### System Requirements
- PHP 8.1 or higher with CLI support
- MySQL/MariaDB database
- Redis server (recommended for production)
- Linux server with systemd (for service management)
- At least 2GB RAM and 2 CPU cores for 500+ concurrent connections

### PHP Extensions Required
```bash
# Install required PHP extensions
sudo apt-get install php8.1-cli php8.1-mysql php8.1-redis php8.1-curl php8.1-json php8.1-mbstring
```

### Composer Dependencies
```bash
# Install WebSocket server dependencies
composer require ratchet/pawl react/socket
```

## Installation Steps

### 1. Database Setup

Run the database migrations to create the required tables:

```bash
# Run real-time scoring migrations
php database/console/migrate.php
```

This will create the following tables:
- `live_scoring_sessions` - Manages scoring sessions
- `live_score_updates` - Tracks individual score updates
- `websocket_connections` - Manages WebSocket connections

### 2. WebSocket Server Configuration

#### Test the WebSocket Server
```bash
# Test WebSocket functionality
php test-websocket-connection.php
```

#### Manual Server Management
```bash
# Make the manager script executable
chmod +x websocket-manager.sh

# Start the WebSocket server
./websocket-manager.sh start

# Check server status
./websocket-manager.sh status

# View logs
./websocket-manager.sh logs

# Stop the server
./websocket-manager.sh stop
```

#### Production Service Setup (Systemd)
```bash
# Copy service file to systemd directory
sudo cp gscms-websocket.service /etc/systemd/system/

# Update paths in the service file to match your installation
sudo nano /etc/systemd/system/gscms-websocket.service

# Enable and start the service
sudo systemctl daemon-reload
sudo systemctl enable gscms-websocket
sudo systemctl start gscms-websocket

# Check service status
sudo systemctl status gscms-websocket

# View service logs
sudo journalctl -u gscms-websocket -f
```

### 3. Firewall Configuration

```bash
# Open WebSocket port (8080)
sudo ufw allow 8080/tcp
sudo ufw reload

# Or for iptables:
sudo iptables -A INPUT -p tcp --dport 8080 -j ACCEPT
```

### 4. Nginx/Apache Configuration

#### Nginx Proxy Configuration
```nginx
# Add to your nginx virtual host
location /ws {
    proxy_pass http://localhost:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 86400;
}
```

#### Apache Proxy Configuration
```apache
# Enable required modules
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod proxy_wstunnel

# Add to your virtual host
ProxyRequests Off
ProxyPreserveHost On

# WebSocket proxy
ProxyPass /ws ws://localhost:8080/
ProxyPassReverse /ws ws://localhost:8080/

# HTTP proxy for WebSocket handshake
ProxyPass /websocket-info http://localhost:8080/
ProxyPassReverse /websocket-info http://localhost:8080/
```

### 5. Redis Configuration (Optional but Recommended)

```bash
# Install Redis
sudo apt-get install redis-server

# Configure Redis for production
sudo nano /etc/redis/redis.conf

# Key settings to update:
# maxmemory 512mb
# maxmemory-policy allkeys-lru
# save 900 1
# save 300 10
# save 60 10000

# Restart Redis
sudo systemctl restart redis-server
```

### 6. SSL/HTTPS Configuration

For secure WebSocket connections (WSS), ensure your web server has SSL configured:

```bash
# Generate SSL certificate (Let's Encrypt example)
sudo certbot --nginx -d yourdomain.com

# Update WebSocket URL in views to use wss:// instead of ws://
```

## Testing the Deployment

### 1. Server Connection Test
```bash
# Test WebSocket server directly
php test-websocket-connection.php

# Expected output: All tests should pass
```

### 2. Web Interface Test
1. Navigate to `/admin/scoring/sessions` to create a scoring session
2. Access judge interface at `/judge/scoring/{competitionId}/{teamId}`
3. Open public scoreboard at `/scoreboard/{sessionId}`
4. Test real-time updates between judge and scoreboard interfaces

### 3. Load Testing (Optional)
```bash
# Install WebSocket test tool
npm install -g wscat

# Test concurrent connections
for i in {1..50}; do
    wscat -c ws://localhost:8080 &
done
```

## Monitoring and Maintenance

### System Monitoring
```bash
# Check WebSocket server status
./websocket-manager.sh status

# Monitor system resources
htop
iotop
netstat -an | grep 8080

# Check logs
tail -f storage/logs/websocket.log
tail -f storage/logs/websocket-error.log
```

### Database Monitoring
```sql
-- Monitor active sessions
SELECT * FROM live_scoring_sessions WHERE status = 'active';

-- Check connection count
SELECT COUNT(*) FROM websocket_connections WHERE disconnected_at IS NULL;

-- Monitor score updates
SELECT session_id, COUNT(*) as updates 
FROM live_score_updates 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY session_id;
```

### Performance Optimization

#### PHP Configuration
```ini
; php.ini optimizations for WebSocket server
memory_limit = 512M
max_execution_time = 0
max_input_time = -1
pcntl.signals = On
```

#### System Optimization
```bash
# Increase file descriptor limits
echo "www-data soft nofile 65536" >> /etc/security/limits.conf
echo "www-data hard nofile 65536" >> /etc/security/limits.conf

# Optimize TCP settings for WebSocket
echo "net.ipv4.tcp_keepalive_time = 120" >> /etc/sysctl.conf
echo "net.ipv4.tcp_keepalive_intvl = 30" >> /etc/sysctl.conf
echo "net.ipv4.tcp_keepalive_probes = 3" >> /etc/sysctl.conf
sysctl -p
```

## Troubleshooting

### Common Issues

#### 1. "Port 8080 already in use"
```bash
# Find process using port 8080
sudo lsof -i :8080
sudo netstat -tulpn | grep 8080

# Kill the process
sudo kill -9 [PID]
```

#### 2. "Permission denied" errors
```bash
# Fix file permissions
chmod +x websocket-manager.sh
chmod +x websocket-server.php
chown -R www-data:www-data storage/
```

#### 3. WebSocket connection fails
```bash
# Check if server is running
./websocket-manager.sh status

# Test direct connection
telnet localhost 8080

# Check firewall
sudo ufw status
sudo iptables -L -n
```

#### 4. High memory usage
```bash
# Monitor memory usage
ps aux | grep websocket-server

# Check for memory leaks in logs
grep -i "memory" storage/logs/websocket-error.log

# Restart server periodically (if needed)
crontab -e
# Add: 0 2 * * * /var/www/html/GSCMS/websocket-manager.sh restart
```

### Log Analysis
```bash
# Search for errors
grep -i "error\|exception\|fatal" storage/logs/websocket.log

# Monitor connection patterns
grep "New connection" storage/logs/websocket.log | tail -20

# Check for conflicts
grep "conflict" storage/logs/websocket.log
```

## Security Considerations

### 1. Authentication
- WebSocket connections are authenticated via session validation
- Judge authentication is required for score submission
- Spectator connections use session-based access codes

### 2. Rate Limiting
The system includes built-in rate limiting:
- Score updates: Maximum 1 per second per judge
- Connection attempts: Maximum 5 per minute per IP
- Message size: Limited to 64KB per message

### 3. Data Validation
- All score updates are validated before processing
- SQL injection prevention through parameterized queries
- XSS protection in all user-facing interfaces

### 4. Network Security
```bash
# Restrict WebSocket access to specific IPs (if needed)
sudo ufw allow from 192.168.1.0/24 to any port 8080

# Enable fail2ban for WebSocket abuse
# Create custom fail2ban filter for WebSocket logs
```

## Production Checklist

- [ ] Database migrations completed
- [ ] WebSocket server starts successfully
- [ ] Systemd service configured and enabled
- [ ] Firewall rules configured
- [ ] Nginx/Apache proxy configured
- [ ] SSL certificate installed (for WSS)
- [ ] Redis server configured (if using)
- [ ] Monitoring tools set up
- [ ] Log rotation configured
- [ ] Backup procedures in place
- [ ] Load testing completed
- [ ] Security audit completed
- [ ] Documentation updated

## Support

### Log Files
- WebSocket output: `storage/logs/websocket.log`
- WebSocket errors: `storage/logs/websocket-error.log`
- Application logs: `storage/logs/app.log`

### Useful Commands
```bash
# Quick health check
./websocket-manager.sh status

# Emergency restart
./websocket-manager.sh restart

# View recent activity
./websocket-manager.sh logs | tail -50

# Monitor live connections
watch "echo 'SHOW PROCESSLIST;' | mysql -u root -p gscms | grep Sleep | wc -l"
```

For additional support, check the application logs and review the scoring system architecture in `real-timeScoringAndJudging.md`.