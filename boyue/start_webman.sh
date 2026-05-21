#!/bin/bash

###############################################################################
# Webman Server Management Script
# Purpose: Start, stop, restart, and verify Webman server
# Usage: ./start_webman.sh [start|stop|restart|status|reload]
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
WEBMAN_DIR="/www/wwwroot/okwink6/boyue"
START_FILE="$WEBMAN_DIR/start.php"
PID_FILE="$WEBMAN_DIR/runtime/webman.pid"
HTTP_PORT=8788
WS_PORT=8789
ADMIN_WS_PORT=8790
HEALTH_ENDPOINT="http://0.0.0.0:$HTTP_PORT"

# Helper functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"
}

# Check if running as correct user
check_user() {
    CURRENT_USER=$(whoami)
    if [ "$CURRENT_USER" = "root" ]; then
        print_warning "Running as root. Consider using 'www' user for production."
    fi
}

# Check if webman is running
is_running() {
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if ps -p "$PID" > /dev/null 2>&1; then
            return 0
        fi
    fi
    
    # Fallback: check by process name
    if pgrep -f "start.php" > /dev/null 2>&1; then
        return 0
    fi
    
    return 1
}

# Get webman process info
get_process_info() {
    ps aux | grep -E "webman|start.php" | grep -v grep || echo "No processes found"
}

# Check port availability
check_port() {
    local port=$1
    local service=$2
    
    if netstat -tlnp 2>/dev/null | grep ":$port " > /dev/null || ss -tlnp 2>/dev/null | grep ":$port " > /dev/null; then
        print_success "$service listening on port $port"
        return 0
    else
        print_error "$service NOT listening on port $port"
        return 1
    fi
}

# Stop webman
stop_webman() {
    print_header "Stopping Webman Server"
    
    if ! is_running; then
        print_warning "Webman is not running"
        return 0
    fi
    
    print_info "Stopping webman gracefully..."
    cd "$WEBMAN_DIR"
    php start.php stop
    
    # Wait for process to stop
    local count=0
    while is_running && [ $count -lt 10 ]; do
        sleep 1
        count=$((count + 1))
        echo -n "."
    done
    echo ""
    
    if is_running; then
        print_warning "Graceful stop failed, forcing stop..."
        pkill -9 -f "start.php" || true
        sleep 2
    fi
    
    if ! is_running; then
        print_success "Webman stopped successfully"
        return 0
    else
        print_error "Failed to stop webman"
        return 1
    fi
}

# Start webman
start_webman() {
    print_header "Starting Webman Server"
    
    if is_running; then
        print_warning "Webman is already running"
        show_status
        return 0
    fi
    
    # Check if start.php exists
    if [ ! -f "$START_FILE" ]; then
        print_error "start.php not found at $START_FILE"
        return 1
    fi
    
    # Check for port conflicts
    print_info "Checking for port conflicts..."
    local port_conflict=0
    
    for port in $HTTP_PORT $WS_PORT $ADMIN_WS_PORT; do
        if netstat -tlnp 2>/dev/null | grep ":$port " > /dev/null || ss -tlnp 2>/dev/null | grep ":$port " > /dev/null; then
            print_error "Port $port is already in use"
            netstat -tlnp 2>/dev/null | grep ":$port " || ss -tlnp 2>/dev/null | grep ":$port "
            port_conflict=1
        fi
    done
    
    if [ $port_conflict -eq 1 ]; then
        print_error "Cannot start: ports are in use"
        return 1
    fi
    
    # Start webman in daemon mode
    print_info "Starting webman in daemon mode..."
    cd "$WEBMAN_DIR"
    php start.php start -d
    
    # Wait for startup
    sleep 3
    
    # Verify startup
    if is_running; then
        print_success "Webman started successfully"
        
        # Wait a bit more for ports to bind
        sleep 2
        
        # Verify ports
        print_info "Verifying services..."
        check_port $HTTP_PORT "HTTP Server"
        check_port $WS_PORT "WebSocket Server"
        check_port $ADMIN_WS_PORT "Admin WebSocket"
        
        # Test health endpoint
        print_info "Testing health endpoint..."
        if curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "$HEALTH_ENDPOINT" > /dev/null 2>&1; then
            print_success "Health check passed"
        else
            print_warning "Health check failed (this may be normal if no route is configured)"
        fi
        
        show_status
        return 0
    else
        print_error "Failed to start webman"
        print_info "Check logs at: $WEBMAN_DIR/runtime/logs/"
        return 1
    fi
}

# Restart webman
restart_webman() {
    print_header "Restarting Webman Server"
    stop_webman
    sleep 2
    start_webman
}

# Reload webman (graceful restart)
reload_webman() {
    print_header "Reloading Webman Server"
    
    if ! is_running; then
        print_error "Webman is not running. Use 'start' instead."
        return 1
    fi
    
    print_info "Reloading webman..."
    cd "$WEBMAN_DIR"
    php start.php reload
    
    sleep 2
    
    if is_running; then
        print_success "Webman reloaded successfully"
        show_status
        return 0
    else
        print_error "Reload failed"
        return 1
    fi
}

# Show status
show_status() {
    print_header "Webman Server Status"
    
    if is_running; then
        print_success "Webman is RUNNING"
        echo ""
        
        print_info "Process Information:"
        get_process_info | head -5
        echo ""
        
        print_info "Listening Ports:"
        check_port $HTTP_PORT "HTTP Server" || true
        check_port $WS_PORT "WebSocket Server" || true
        check_port $ADMIN_WS_PORT "Admin WebSocket" || true
        echo ""
        
        print_info "Connection Test:"
        echo "  HTTP: curl http://0.0.0.0:$HTTP_PORT"
        echo "  WebSocket: ws://0.0.0.0:$WS_PORT"
        echo "  Admin WS: ws://0.0.0.0:$ADMIN_WS_PORT"
        
    else
        print_error "Webman is NOT RUNNING"
        echo ""
        print_info "To start: ./start_webman.sh start"
    fi
}

# Show help
show_help() {
    cat << EOF
Webman Server Management Script

Usage: $0 [COMMAND]

Commands:
    start       Start webman server
    stop        Stop webman server
    restart     Restart webman server (stop + start)
    reload      Reload webman server (graceful restart)
    status      Show server status
    help        Show this help message

Examples:
    $0 start
    $0 stop
    $0 restart
    $0 status

Configuration:
    HTTP Port:        $HTTP_PORT
    WebSocket Port:   $WS_PORT
    Admin WS Port:    $ADMIN_WS_PORT
    Working Dir:      $WEBMAN_DIR

EOF
}

# Main script
main() {
    check_user
    
    case "${1:-status}" in
        start)
            start_webman
            ;;
        stop)
            stop_webman
            ;;
        restart)
            restart_webman
            ;;
        reload)
            reload_webman
            ;;
        status)
            show_status
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            print_error "Unknown command: $1"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

# Run main function
main "$@"