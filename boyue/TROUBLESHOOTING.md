# Webman Server Troubleshooting Guide

This guide helps you diagnose and fix common issues with the Webman server.

## Table of Contents

1. [Quick Diagnostics](#quick-diagnostics)
2. [Common Issues](#common-issues)
3. [Port Issues](#port-issues)
4. [Server Not Starting](#server-not-starting)
5. [Connection Issues](#connection-issues)
6. [Performance Issues](#performance-issues)
7. [Logs and Debugging](#logs-and-debugging)

---

## Quick Diagnostics

### Check Server Status
```bash
cd /www/wwwroot/okwink6/boyue
./start_webman.sh status
```

### Check All Ports
```bash
# Check what's listening
netstat -tlnp | grep -E "8788|8789|8790"
# or
ss -tlnp | grep -E "8788|8789|8790"
```

### Check Processes
```bash
ps aux | grep -E "webman|start.php" | grep -v grep
```

### Quick Health Check
```bash
curl -v http://0.0.0.0:8788/api/v1/gscplus/health
```

---

## Common Issues

### Issue 1: "Failed to connect to 0.0.0.0 port 8788"

**Symptoms:**
```
curl: (7) Failed to connect to 0.0.0.0 port 8788 after 0 ms: Couldn't connect to server
```

**Causes:**
- Server is not running
- Server is running on a different port
- Firewall is blocking the port

**Solutions:**

1. **Check if server is running:**
   ```bash
   ps aux | grep start.php
   ```

2. **Start the server:**
   ```bash
   cd /www/wwwroot/okwink6/boyue
   ./start_webman.sh start
   ```

3. **Check configuration:**
   ```bash
   grep -n "listen" config/process.php
   # Should show: 'listen' => 'http://0.0.0.0:8788',
   ```

4. **Check firewall:**
   ```bash
   # UFW
   sudo ufw status
   sudo ufw allow 8788/tcp
   
   # Firewalld
   sudo firewall-cmd --list-ports
   sudo firewall-cmd --add-port=8788/tcp --permanent
   sudo firewall-cmd --reload
   ```

---

### Issue 2: "Port already in use"

**Symptoms:**
```
Address already in use
```

**Solutions:**

1. **Find what's using the port:**
   ```bash
   netstat -tlnp | grep 8788
   # or
   lsof -i :8788
   ```

2. **Kill the process:**
   ```bash
   # Get PID from above command
   kill -9 <PID>
   ```

3. **Or use the management script:**
   ```bash
   cd /www/wwwroot/okwink6/boyue
   ./start_webman.sh restart
   ```

---

### Issue 3: Server starts but immediately stops

**Causes:**
- PHP errors in code
- Missing dependencies
- Permission issues
- Configuration errors

**Solutions:**

1. **Check error logs:**
   ```bash
   tail -50 runtime/logs/workerman.log
   tail -50 runtime/logs/webman-$(date +%Y-%m-%d).log
   ```

2. **Check PHP errors:**
   ```bash
   php -l start.php
   ```

3. **Check permissions:**
   ```bash
   ls -la runtime/
   # Should be writable by www user
   chmod -R 755 runtime/
   chown -R www:www runtime/
   ```

4. **Test configuration:**
   ```bash
   php start.php check
   ```

---

### Issue 4: "Permission denied"

**Symptoms:**
```
Permission denied when writing to runtime/
```

**Solutions:**

1. **Fix permissions:**
   ```bash
   cd /www/wwwroot/okwink6/boyue
   chmod -R 755 runtime/
   chown -R www:www runtime/
   ```

2. **Check user:**
   ```bash
   whoami
   # Should be 'www' or 'root' in development
   ```

3. **Run as correct user:**
   ```bash
   sudo -u www ./start_webman.sh start
   ```

---

### Issue 5: Server running on wrong port

**Symptoms:**
- Server shows port 8788 in config but runs on different port
- Old port (5001) still being used

**Solutions:**

1. **Stop all webman processes:**
   ```bash
   pkill -9 -f start.php
   ```

2. **Verify configuration:**
   ```bash
   grep -n "listen" config/process.php
   ```

3. **Clear cache:**
   ```bash
   rm -rf runtime/cache/*
   ```

4. **Restart server:**
   ```bash
   ./start_webman.sh start
   ```

---

## Port Issues

### Default Ports

| Service | Port | Protocol |
|---------|------|----------|
| HTTP Server | 8788 | HTTP |
| WebSocket | 8789 | WebSocket |
| Admin WebSocket | 8790 | WebSocket |

### Check Port Configuration

```bash
# Check process.php
cat config/process.php | grep -A 2 "listen"

# Expected output:
# 'listen' => 'http://0.0.0.0:8788',
# 'listen' => 'websocket://0.0.0.0:8789',
# 'listen' => 'websocket://0.0.0.0:8790',
```

### Change Port

1. Edit `config/process.php`:
   ```php
   'webman' => [
       'handler' => Http::class,
       'listen' => 'http://0.0.0.0:NEW_PORT',
       // ...
   ],
   ```

2. Restart server:
   ```bash
   ./start_webman.sh restart
   ```

---

## Server Not Starting

### Checklist

- [ ] PHP is installed and accessible
- [ ] Composer dependencies are installed
- [ ] Runtime directory exists and is writable
- [ ] No port conflicts
- [ ] No syntax errors in code
- [ ] Sufficient system resources

### Diagnostic Commands

```bash
# Check PHP version
php -v

# Check composer
composer --version

# Check dependencies
composer install --no-dev

# Check syntax
php -l start.php

# Check system resources
free -h
df -h

# Check for errors
php start.php start
# (Don't use -d flag to see errors)
```

---

## Connection Issues

### Cannot Connect from External Network

**Causes:**
- Server listening on 127.0.0.1 instead of 0.0.0.0
- Firewall blocking connections
- Network routing issues

**Solutions:**

1. **Verify listening address:**
   ```bash
   netstat -tlnp | grep 8788
   # Should show: 0.0.0.0:8788, not 127.0.0.1:8788
   ```

2. **Update configuration if needed:**
   ```php
   // config/process.php
   'listen' => 'http://0.0.0.0:8788',  // ✓ Correct
   'listen' => 'http://127.0.0.1:8788', // ✗ Wrong
   ```

3. **Test from server:**
   ```bash
   curl http://127.0.0.1:8788
   curl http://0.0.0.0:8788
   ```

4. **Test from external:**
   ```bash
   # Get server IP
   hostname -I
   
   # Test from another machine
   curl http://SERVER_IP:8788
   ```

---

### WebSocket Connection Failed

**Solutions:**

1. **Check WebSocket port:**
   ```bash
   netstat -tlnp | grep 8789
   ```

2. **Test WebSocket:**
   ```bash
   # Install wscat if needed
   npm install -g wscat
   
   # Test connection
   wscat -c ws://0.0.0.0:8789
   ```

3. **Check WebSocket configuration:**
   ```bash
   grep -A 5 "websocket" config/process.php
   ```

---

## Performance Issues

### High CPU Usage

**Solutions:**

1. **Check worker count:**
   ```bash
   ps aux | grep "webman.*worker" | wc -l
   ```

2. **Adjust worker count in config/process.php:**
   ```php
   'count' => cpu_count() * 2, // Reduce multiplier
   ```

3. **Monitor processes:**
   ```bash
   top -p $(pgrep -d',' -f start.php)
   ```

### High Memory Usage

**Solutions:**

1. **Check memory per worker:**
   ```bash
   ps aux | grep webman | awk '{print $6, $11}'
   ```

2. **Reduce worker count**

3. **Enable memory monitoring:**
   ```bash
   # Check config/process.php monitor settings
   grep -A 10 "monitor" config/process.php
   ```

---

## Logs and Debugging

### Log Locations

```bash
# Main log
runtime/logs/workerman.log

# Daily logs
runtime/logs/webman-YYYY-MM-DD.log

# Stdout
runtime/logs/stdout.log

# Error logs
runtime/logs/error-YYYY-MM-DD.log
```

### View Logs

```bash
# Real-time monitoring
tail -f runtime/logs/workerman.log

# Last 50 lines
tail -50 runtime/logs/webman-$(date +%Y-%m-%d).log

# Search for errors
grep -i error runtime/logs/*.log

# Search for specific issue
grep -i "connection" runtime/logs/*.log
```

### Enable Debug Mode

1. **Edit config/app.php:**
   ```php
   'debug' => true,
   ```

2. **Restart server:**
   ```bash
   ./start_webman.sh restart
   ```

3. **Check detailed logs**

---

## Advanced Troubleshooting

### Strace Server Process

```bash
# Get PID
PID=$(pgrep -f "start.php" | head -1)

# Trace system calls
strace -p $PID -f -e trace=network
```

### Check Network Connections

```bash
# Active connections
netstat -anp | grep 8788

# Connection states
ss -s
```

### Test with Different Tools

```bash
# wget
wget -O- http://0.0.0.0:8788

# telnet
telnet 0.0.0.0 8788

# nc (netcat)
nc -zv 0.0.0.0 8788
```

---

## Getting Help

### Information to Provide

When asking for help, include:

1. **Server status:**
   ```bash
   ./start_webman.sh status
   ```

2. **Error logs:**
   ```bash
   tail -50 runtime/logs/workerman.log
   ```

3. **Configuration:**
   ```bash
   grep -n "listen" config/process.php
   ```

4. **System info:**
   ```bash
   uname -a
   php -v
   free -h
   ```

5. **Network info:**
   ```bash
   netstat -tlnp | grep -E "8788|8789|8790"
   ```

---

## Quick Reference

### Management Commands

```bash
# Start server
./start_webman.sh start

# Stop server
./start_webman.sh stop

# Restart server
./start_webman.sh restart

# Reload (graceful restart)
./start_webman.sh reload

# Check status
./start_webman.sh status
```

### Verification Commands

```bash
# Verify network configuration
cd /www/wwwroot/okwink6
./verify_network.sh

# Test health endpoint
curl http://0.0.0.0:8788/api/v1/gscplus/health

# Check all services
netstat -tlnp | grep -E "8788|8789|8790"
```

---

## Emergency Recovery

If nothing works, try this complete reset:

```bash
# 1. Stop everything
pkill -9 -f start.php

# 2. Clean runtime
cd /www/wwwroot/okwink6/boyue
rm -rf runtime/cache/*
rm -f runtime/webman.pid

# 3. Fix permissions
chmod -R 755 runtime/
chown -R www:www runtime/

# 4. Verify configuration
grep -n "listen" config/process.php

# 5. Start fresh
./start_webman.sh start

# 6. Verify
./start_webman.sh status
```

---

## Contact & Support

For additional help:
- Check logs first: `runtime/logs/`
- Run diagnostics: `./start_webman.sh status`
- Verify network: `./verify_network.sh`
- Review this guide thoroughly

**Last Updated:** 2026-05-20