#!/bin/bash

# WebSocket Server Manager Script for GSCMS Real-Time Scoring
# Usage: ./websocket-manager.sh [start|stop|restart|status|logs]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEBSOCKET_SCRIPT="$SCRIPT_DIR/websocket-server.php"
PID_FILE="$SCRIPT_DIR/websocket.pid"
LOG_FILE="$SCRIPT_DIR/storage/logs/websocket.log"
ERROR_LOG="$SCRIPT_DIR/storage/logs/websocket-error.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Check if WebSocket server is running
is_running() {
    if [ -f "$PID_FILE" ]; then
        local pid=$(cat "$PID_FILE")
        if ps -p "$pid" > /dev/null 2>&1; then
            return 0
        else
            # PID file exists but process is dead, remove stale PID file
            rm -f "$PID_FILE"
            return 1
        fi
    fi
    return 1
}

# Start WebSocket server
start_server() {
    print_status "Starting SciBOTICS WebSocket Server..."
    
    if is_running; then
        print_warning "WebSocket server is already running (PID: $(cat $PID_FILE))"
        return 1
    fi
    
    # Check if log directory exists
    mkdir -p "$(dirname "$LOG_FILE")"
    mkdir -p "$(dirname "$ERROR_LOG")"
    
    # Check if PHP script exists
    if [ ! -f "$WEBSOCKET_SCRIPT" ]; then
        print_error "WebSocket server script not found: $WEBSOCKET_SCRIPT"
        return 1
    fi
    
    # Check if port 8080 is available
    if netstat -tuln | grep -q ":8080 "; then
        print_error "Port 8080 is already in use. Please stop the service using that port first."
        return 1
    fi
    
    # Start server in background
    nohup php "$WEBSOCKET_SCRIPT" > "$LOG_FILE" 2> "$ERROR_LOG" & 
    local pid=$!
    
    # Save PID
    echo $pid > "$PID_FILE"
    
    # Wait a moment and check if it's still running
    sleep 2
    if is_running; then
        print_success "WebSocket server started successfully (PID: $pid)"
        print_status "Server is listening on ws://localhost:8080"
        print_status "Logs: $LOG_FILE"
        print_status "Error logs: $ERROR_LOG"
        return 0
    else
        print_error "Failed to start WebSocket server"
        print_status "Check error log: $ERROR_LOG"
        return 1
    fi
}

# Stop WebSocket server
stop_server() {
    print_status "Stopping SciBOTICS WebSocket Server..."
    
    if ! is_running; then
        print_warning "WebSocket server is not running"
        return 1
    fi
    
    local pid=$(cat "$PID_FILE")
    
    # Try graceful shutdown first
    kill -TERM "$pid" 2>/dev/null
    
    # Wait for graceful shutdown
    local count=0
    while [ $count -lt 10 ] && is_running; do
        sleep 1
        count=$((count + 1))
    done
    
    # If still running, force kill
    if is_running; then
        print_warning "Graceful shutdown failed, forcing termination..."
        kill -KILL "$pid" 2>/dev/null
        sleep 1
    fi
    
    # Clean up PID file
    rm -f "$PID_FILE"
    
    if ! is_running; then
        print_success "WebSocket server stopped successfully"
        return 0
    else
        print_error "Failed to stop WebSocket server"
        return 1
    fi
}

# Restart WebSocket server
restart_server() {
    print_status "Restarting SciBOTICS WebSocket Server..."
    stop_server
    sleep 2
    start_server
}

# Show server status
show_status() {
    print_status "SciBOTICS WebSocket Server Status"
    echo "=================================="
    
    if is_running; then
        local pid=$(cat "$PID_FILE")
        print_success "Server is RUNNING (PID: $pid)"
        
        # Show process info
        if command -v ps > /dev/null; then
            echo ""
            echo "Process Details:"
            ps -p "$pid" -o pid,ppid,cmd,etime,pcpu,pmem 2>/dev/null || echo "Unable to get process details"
        fi
        
        # Show port status
        echo ""
        echo "Network Status:"
        if command -v netstat > /dev/null; then
            netstat -tuln | grep ":8080 " || echo "Port 8080 not found in netstat"
        else
            echo "netstat not available"
        fi
        
        # Show recent log entries
        echo ""
        echo "Recent Log Entries (last 10 lines):"
        if [ -f "$LOG_FILE" ]; then
            tail -n 10 "$LOG_FILE"
        else
            echo "Log file not found"
        fi
        
    else
        print_error "Server is NOT RUNNING"
        
        # Check if there are recent errors
        if [ -f "$ERROR_LOG" ] && [ -s "$ERROR_LOG" ]; then
            echo ""
            echo "Recent Errors (last 5 lines):"
            tail -n 5 "$ERROR_LOG"
        fi
    fi
    
    echo ""
    echo "Log Files:"
    echo "  Output: $LOG_FILE"
    echo "  Errors: $ERROR_LOG"
}

# Show logs
show_logs() {
    local lines=${2:-50}
    local log_type=${1:-"output"}
    
    case "$log_type" in
        "error" | "err")
            if [ -f "$ERROR_LOG" ]; then
                print_status "WebSocket Error Log (last $lines lines):"
                tail -n "$lines" "$ERROR_LOG"
            else
                print_warning "Error log file not found: $ERROR_LOG"
            fi
            ;;
        "output" | "out" | *)
            if [ -f "$LOG_FILE" ]; then
                print_status "WebSocket Output Log (last $lines lines):"
                tail -n "$lines" "$LOG_FILE"
            else
                print_warning "Output log file not found: $LOG_FILE"
            fi
            ;;
    esac
}

# Show help
show_help() {
    echo "SciBOTICS WebSocket Server Manager"
    echo "=================================="
    echo ""
    echo "Usage: $0 [COMMAND] [OPTIONS]"
    echo ""
    echo "Commands:"
    echo "  start         Start the WebSocket server"
    echo "  stop          Stop the WebSocket server"
    echo "  restart       Restart the WebSocket server"
    echo "  status        Show server status and recent logs"
    echo "  logs [type]   Show logs (type: output|error, default: output)"
    echo "  help          Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 start                    # Start the server"
    echo "  $0 logs error              # Show error logs"
    echo "  $0 logs output 100         # Show last 100 output log lines"
    echo ""
    echo "Configuration:"
    echo "  Server Script: $WEBSOCKET_SCRIPT"
    echo "  PID File:      $PID_FILE"
    echo "  Output Log:    $LOG_FILE"
    echo "  Error Log:     $ERROR_LOG"
    echo "  Listen Port:   8080"
}

# Main script logic
case "${1:-help}" in
    "start")
        start_server
        ;;
    "stop")
        stop_server
        ;;
    "restart")
        restart_server
        ;;
    "status")
        show_status
        ;;
    "logs")
        show_logs "$2" "$3"
        ;;
    "help" | "--help" | "-h")
        show_help
        ;;
    *)
        print_error "Unknown command: $1"
        echo ""
        show_help
        exit 1
        ;;
esac