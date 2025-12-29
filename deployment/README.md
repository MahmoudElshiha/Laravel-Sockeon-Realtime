# Laravel-Sockeon Production Deployment

This directory contains all the necessary configuration files and scripts for deploying Laravel-Sockeon to a production server with WebSocket (WSS) support.

## Contents

- **nginx/laravel-sockeon.conf** - Complete Nginx configuration for Laravel + WebSocket with SSL
- **supervisor/sockeon-websocket.conf** - Supervisor configuration for running Sockeon WebSocket server
- **env.production.example** - Production environment configuration template
- **deploy.sh** - Automated deployment script

## Quick Start

### 1. Initial Server Setup

Follow the complete guide in `.agent/workflows/deploy-production.md` or run:

```bash
/deploy-production
```

### 2. Copy Configuration Files

```bash
# Copy Nginx configuration
sudo cp deployment/nginx/laravel-sockeon.conf /etc/nginx/sites-available/laravel-sockeon
sudo ln -s /etc/nginx/sites-available/laravel-sockeon /etc/nginx/sites-enabled/

# Copy Supervisor configuration
sudo cp deployment/supervisor/sockeon-websocket.conf /etc/supervisor/conf.d/

# Copy environment file
cp deployment/env.production.example .env
# Edit .env with your actual values
nano .env
```

### 3. Setup SSL Certificates

```bash
# Obtain SSL certificates
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com -d ws.yourdomain.com
```

### 4. Start Services

```bash
# Test and reload Nginx
sudo nginx -t
sudo systemctl reload nginx

# Start WebSocket server with Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sockeon-websocket
```

### 5. Deploy Updates

```bash
# Make deploy script executable
chmod +x deployment/deploy.sh

# Run deployment
./deployment/deploy.sh
```

## Important Notes

### Domain Configuration

Replace `yourdomain.com` with your actual domain in:
- `deployment/nginx/laravel-sockeon.conf`
- `deployment/env.production.example`
- `.env` file

### WebSocket Subdomain

You need to configure a subdomain for WebSocket connections:
- Main app: `https://yourdomain.com`
- WebSocket: `wss://ws.yourdomain.com`

Add DNS A record for `ws.yourdomain.com` pointing to your server IP.

### Firewall Rules

Ensure these ports are open:
- 22 (SSH)
- 80 (HTTP - redirects to HTTPS)
- 443 (HTTPS - for both web and WebSocket)

Port 6001 should NOT be exposed externally - it's only for internal use.

### Security Checklist

- [ ] Strong database password set
- [ ] APP_DEBUG=false in production
- [ ] APP_KEY generated
- [ ] SSL certificates installed
- [ ] Firewall configured
- [ ] Root SSH login disabled
- [ ] Regular backups configured
- [ ] Log rotation enabled

## Monitoring

### Check WebSocket Server Status

```bash
sudo supervisorctl status sockeon-websocket
```

### View WebSocket Logs

```bash
# Real-time logs
sudo supervisorctl tail -f sockeon-websocket

# Or from file
tail -f storage/logs/sockeon-websocket.log
```

### View Nginx Logs

```bash
# Access logs
sudo tail -f /var/log/nginx/laravel-sockeon-access.log
sudo tail -f /var/log/nginx/sockeon-ws-access.log

# Error logs
sudo tail -f /var/log/nginx/laravel-sockeon-error.log
sudo tail -f /var/log/nginx/sockeon-ws-error.log
```

## Troubleshooting

### WebSocket Connection Fails

1. Check if Sockeon server is running:
   ```bash
   sudo supervisorctl status sockeon-websocket
   ```

2. Check logs for errors:
   ```bash
   sudo supervisorctl tail sockeon-websocket
   ```

3. Verify Nginx is proxying correctly:
   ```bash
   sudo nginx -t
   sudo tail -f /var/log/nginx/sockeon-ws-error.log
   ```

4. Test local WebSocket connection:
   ```bash
   # Install wscat
   npm install -g wscat
   
   # Test local connection
   wscat -c ws://127.0.0.1:6001
   ```

### SSL Certificate Issues

```bash
# Check certificate status
sudo certbot certificates

# Renew certificates
sudo certbot renew
sudo systemctl reload nginx
```

### Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/laravel-sockeon/storage
sudo chmod -R 775 /var/www/laravel-sockeon/storage
```

## Resources

- [Complete Deployment Guide](.agent/workflows/deploy-production.md)
- [Sockeon Documentation](https://sockeon.com/v2.0/)
- [Laravel Deployment Docs](https://laravel.com/docs/deployment)
