# Production Setup for sockeon.pregnazone.com

## Overview

This guide is specifically for your RunCloud/control panel managed server at `sockeon.pregnazone.com`.

## Step 1: Update Environment Configuration

Edit your `.env` file on the server:

```bash
cd /home/pregnazone-sockeon/htdocs/sockeon.pregnazone.com
nano .env
```

Update these values:

```env
# WebSocket Configuration
SOCKEON_HOST=127.0.0.1
SOCKEON_PORT=6001

# For frontend JavaScript
VITE_WS_URL=wss://sockeon.pregnazone.com/sockeon
```

## Step 2: Update Nginx Configuration

### Option A: Using Control Panel

1. Go to your hosting control panel
2. Navigate to Nginx configuration for `sockeon.pregnazone.com`
3. Replace the entire configuration with the content from `deployment/nginx/pregnazone-complete.conf`

### Option B: Manual Edit

```bash
# Find your nginx config file (usually in /etc/nginx/sites-available/)
sudo nano /etc/nginx/sites-available/sockeon.pregnazone.com.conf

# Replace with the new configuration
# Then test and reload
sudo nginx -t
sudo systemctl reload nginx
```

### Key Changes Made:

1. **Added WebSocket endpoint** at `/sockeon` path
2. **Proxies to internal port** `127.0.0.1:6001`
3. **Proper WebSocket headers** (Upgrade, Connection)
4. **Long timeouts** for persistent connections (7 days)
5. **Disabled buffering** for real-time communication

## Step 3: Setup Supervisor for WebSocket Server

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/sockeon-websocket.conf
```

Add this content:

```ini
[program:sockeon-websocket]
process_name=%(program_name)s
command=php /home/pregnazone-sockeon/htdocs/sockeon.pregnazone.com/artisan sockeon:serve
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=pregnazone-sockeon
numprocs=1
redirect_stderr=true
stdout_logfile=/home/pregnazone-sockeon/htdocs/sockeon.pregnazone.com/storage/logs/sockeon-websocket.log
stopwaitsecs=3600
```

**Note:** Replace `pregnazone-sockeon` with the actual system user if different.

Start the service:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sockeon-websocket
sudo supervisorctl status sockeon-websocket
```

## Step 4: Update Client-Side JavaScript

Update your WebSocket connection in `resources/views/chat.blade.php` or your JavaScript files:

### Before (Development):
```javascript
const socket = new WebSocket('ws://localhost:8080');
```

### After (Production):
```javascript
const socket = new WebSocket('wss://sockeon.pregnazone.com/sockeon');
```

### Better (Using Environment Variable):
```javascript
const wsUrl = '{{ config('app.ws_url', 'ws://localhost:8080') }}';
const socket = new WebSocket(wsUrl);
```

Then add to `config/app.php`:

```php
'ws_url' => env('VITE_WS_URL', 'ws://localhost:8080'),
```

## Step 5: Fix Current Port Conflict

Before starting with Supervisor, kill the existing process:

```bash
# Find process using port 8080
sudo lsof -i :8080

# Kill it (replace <PID> with actual process ID)
sudo kill -9 <PID>

# Or kill all sockeon processes
sudo pkill -f "artisan sockeon:serve"
```

## Step 6: Clear Laravel Cache

```bash
cd /home/pregnazone-sockeon/htdocs/sockeon.pregnazone.com
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

## Step 7: Test the Setup

### 7.1 Check WebSocket Server Status

```bash
sudo supervisorctl status sockeon-websocket
# Should show: RUNNING
```

### 7.2 Check WebSocket Logs

```bash
sudo supervisorctl tail -f sockeon-websocket
# Or
tail -f /home/pregnazone-sockeon/htdocs/sockeon.pregnazone.com/storage/logs/sockeon-websocket.log
```

### 7.3 Test WebSocket Connection

Open your browser and visit:
```
https://sockeon.pregnazone.com
```

Open browser console (F12) and check for:
- `WebSocket connection established` message
- No SSL/certificate errors
- No connection refused errors

### 7.4 Test from Command Line

```bash
# Install wscat if not available
npm install -g wscat

# Test local connection
wscat -c ws://127.0.0.1:6001

# Test through Nginx (from another machine or use curl)
wscat -c wss://sockeon.pregnazone.com/sockeon
```

## Architecture

```
Internet (WSS)
     ↓
Nginx :443 (SSL Termination)
     ↓
Proxy to /sockeon → Sockeon WebSocket Server :6001 (localhost)
     ↓
Proxy to / → Varnish/PHP-FPM :8080
```

## Important Notes

### 1. WebSocket URL Path
- **Production:** `wss://sockeon.pregnazone.com/sockeon`
- The `/sockeon` path is handled by Nginx and proxied to port 6001

### 2. Port Binding
- Sockeon binds to `127.0.0.1:6001` (internal only)
- Port 6001 is NOT exposed to the internet
- Only Nginx can access it

### 3. SSL/TLS
- Nginx handles SSL termination
- WebSocket connections are encrypted (WSS)
- Uses your existing SSL certificate

### 4. Process Management
- Never run `php artisan sockeon:serve` manually in production
- Always use Supervisor to manage the process
- Supervisor ensures auto-restart on crashes

## Troubleshooting

### Issue: "Address already in use"

```bash
# Find and kill the process
sudo lsof -i :6001
sudo kill -9 <PID>

# Or use supervisor
sudo supervisorctl restart sockeon-websocket
```

### Issue: WebSocket connection refused

```bash
# Check if server is running
sudo supervisorctl status sockeon-websocket

# Check logs
sudo supervisorctl tail sockeon-websocket

# Restart if needed
sudo supervisorctl restart sockeon-websocket
```

### Issue: 502 Bad Gateway

```bash
# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check if Sockeon is listening
sudo netstat -tulpn | grep 6001

# Restart both services
sudo supervisorctl restart sockeon-websocket
sudo systemctl reload nginx
```

### Issue: SSL certificate errors

- Make sure your SSL certificate is valid
- Check certificate in control panel
- Nginx uses the same certificate for both HTTP and WebSocket

## Monitoring

### View Real-time Logs

```bash
# WebSocket server logs
sudo supervisorctl tail -f sockeon-websocket

# Nginx access logs
sudo tail -f /var/log/nginx/access.log | grep sockeon

# Nginx error logs
sudo tail -f /var/log/nginx/error.log
```

### Check Server Status

```bash
# Supervisor status
sudo supervisorctl status

# Nginx status
sudo systemctl status nginx

# Check listening ports
sudo netstat -tulpn | grep -E ':(6001|8080|443)'
```

## Useful Commands

### Supervisor Commands

```bash
# Start
sudo supervisorctl start sockeon-websocket

# Stop
sudo supervisorctl stop sockeon-websocket

# Restart
sudo supervisorctl restart sockeon-websocket

# View logs
sudo supervisorctl tail -f sockeon-websocket

# Status
sudo supervisorctl status sockeon-websocket
```

### Nginx Commands

```bash
# Test configuration
sudo nginx -t

# Reload (no downtime)
sudo systemctl reload nginx

# Restart
sudo systemctl restart nginx

# Status
sudo systemctl status nginx
```

### Application Commands

```bash
cd /home/pregnazone-sockeon/htdocs/sockeon.pregnazone.com

# Clear cache
php artisan config:clear
php artisan cache:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Security Checklist

- [x] WebSocket server binds to localhost only (127.0.0.1)
- [x] SSL/TLS enabled (WSS protocol)
- [x] Port 6001 not exposed to internet
- [x] Nginx handles all external connections
- [x] Long timeouts only for WebSocket endpoint
- [ ] Firewall configured (only 80, 443, 22 open)
- [ ] Regular security updates applied
- [ ] Strong database passwords
- [ ] APP_DEBUG=false in production

## Next Steps

1. ✅ Update `.env` with correct WebSocket settings
2. ✅ Update Nginx configuration
3. ✅ Setup Supervisor
4. ✅ Update client-side JavaScript
5. ✅ Test WebSocket connection
6. 📝 Monitor logs for any issues
7. 📝 Setup automated backups
8. 📝 Configure monitoring/alerts

---

**Server Details:**
- Domain: `sockeon.pregnazone.com`
- App Path: `/home/pregnazone-sockeon/htdocs/sockeon.pregnazone.com`
- WebSocket URL: `wss://sockeon.pregnazone.com/sockeon`
- Internal Port: `6001`
