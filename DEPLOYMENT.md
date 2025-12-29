# Laravel-Sockeon Deployment Guide

Complete guide for deploying Laravel applications with Sockeon WebSocket server to production.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Detailed Setup](#detailed-setup)
- [Configuration Files](#configuration-files)
- [Troubleshooting](#troubleshooting)
- [Maintenance](#maintenance)

## Prerequisites

### Server Requirements

- Ubuntu 20.04+ or similar Linux distribution
- Minimum 2GB RAM
- Root or sudo access
- Domain name with DNS configured

### Software Stack

- PHP 8.1+ with extensions: `cli`, `fpm`, `mysql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `intl`
- Nginx
- MySQL/MariaDB 10.3+
- Composer
- Supervisor
- Certbot (for SSL)

## Quick Start

### 1. Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.1-fpm php8.1-cli php8.1-common php8.1-mysql \
    php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml \
    php8.1-bcmath php8.1-intl

# Install other services
sudo apt install -y nginx mysql-server supervisor certbot python3-certbot-nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Deploy Application

```bash
# Clone repository
sudo mkdir -p /var/www/your-app
cd /var/www/your-app
git clone <your-repo-url> .

# Install dependencies
composer install --optimize-autoloader --no-dev

# Set permissions
sudo chown -R www-data:www-data /var/www/your-app
sudo chmod -R 755 /var/www/your-app
sudo chmod -R 775 storage bootstrap/cache
```

### 3. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env
nano .env
```

**Required `.env` settings:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

# WebSocket Configuration
SOCKEON_HOST=127.0.0.1
SOCKEON_PORT=6001
```

### 4. Setup Database

```bash
# Create database
sudo mysql -u root -p

# In MySQL:
CREATE DATABASE your_database;
CREATE USER 'your_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON your_database.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force
```

### 5. Configure Nginx

Create `/etc/nginx/sites-available/your-app`:

```nginx
# Main HTTPS Server
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/your-app/public;
    index index.php index.html;

    # SSL Configuration (added by Certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # WebSocket endpoint
    location /sockeon {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
        proxy_buffering off;
    }

    # Laravel application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/your-app /etc/nginx/sites-enabled/
sudo nginx -t
```

### 6. Setup SSL

```bash
# Stop Nginx temporarily
sudo systemctl stop nginx

# Obtain certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Start Nginx
sudo systemctl start nginx
```

### 7. Configure Supervisor

Create `/etc/supervisor/conf.d/sockeon-websocket.conf`:

```ini
[program:sockeon-websocket]
process_name=%(program_name)s
command=php /var/www/your-app/artisan sockeon:serve
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/your-app/storage/logs/sockeon-websocket.log
stopwaitsecs=3600
```

Start service:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sockeon-websocket
sudo supervisorctl status sockeon-websocket
```

### 8. Configure Firewall

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 9. Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Configuration Files

### Environment Variables

**Development:**
```env
APP_ENV=local
APP_DEBUG=true
SOCKEON_HOST=0.0.0.0
SOCKEON_PORT=8080
```

**Production:**
```env
APP_ENV=production
APP_DEBUG=false
SOCKEON_HOST=127.0.0.1
SOCKEON_PORT=6001
```

### Client-Side WebSocket URL

Update your JavaScript to use environment-based URLs:

```javascript
@if(config('app.env') === 'production')
    const wsUrl = 'wss://yourdomain.com/sockeon';
@else
    const wsUrl = 'ws://localhost:8080';
@endif

const socket = new WebSocket(wsUrl);
```

## Troubleshooting

### WebSocket Connection Fails

```bash
# Check if server is running
sudo supervisorctl status sockeon-websocket

# View logs
sudo supervisorctl tail -f sockeon-websocket

# Restart
sudo supervisorctl restart sockeon-websocket
```

### Port Already in Use

```bash
# Find process
sudo lsof -i :6001

# Kill process
sudo kill -9 <PID>

# Or kill all
sudo pkill -f "artisan sockeon:serve"
```

### SSL Certificate Issues

```bash
# Check certificates
sudo certbot certificates

# Renew
sudo certbot renew
sudo systemctl reload nginx
```

### Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/your-app/storage
sudo chmod -R 775 /var/www/your-app/storage
```

## Maintenance

### Deployment Script

Create `deploy.sh`:

```bash
#!/bin/bash
set -e

cd /var/www/your-app

# Maintenance mode
php artisan down

# Update code
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo supervisorctl restart sockeon-websocket
sudo systemctl reload php8.1-fpm

# Bring back online
php artisan up

echo "Deployment completed!"
```

Make executable:
```bash
chmod +x deploy.sh
```

### Useful Commands

**Supervisor:**
```bash
sudo supervisorctl status                    # Check status
sudo supervisorctl restart sockeon-websocket # Restart
sudo supervisorctl tail -f sockeon-websocket # View logs
```

**Nginx:**
```bash
sudo nginx -t                 # Test config
sudo systemctl reload nginx   # Reload
sudo systemctl status nginx   # Check status
```

**Laravel:**
```bash
php artisan config:clear  # Clear cache
php artisan cache:clear   # Clear app cache
php artisan queue:work    # Run queue worker
```

### Monitoring

**View Logs:**
```bash
# WebSocket logs
tail -f /var/www/your-app/storage/logs/sockeon-websocket.log

# Laravel logs
tail -f /var/www/your-app/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```

**Check Processes:**
```bash
# WebSocket server
sudo netstat -tulpn | grep 6001

# PHP-FPM
sudo systemctl status php8.1-fpm

# Nginx
sudo systemctl status nginx
```

## Security Best Practices

- ✅ Bind WebSocket server to `127.0.0.1` only
- ✅ Use SSL/TLS for all connections (WSS)
- ✅ Keep `APP_DEBUG=false` in production
- ✅ Use strong database passwords
- ✅ Configure firewall (only ports 22, 80, 443)
- ✅ Regular security updates
- ✅ Disable root SSH login
- ✅ Use environment variables for secrets
- ✅ Regular backups

## Performance Optimization

### Enable OPcache

Edit `/etc/php/8.1/fpm/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### Use Redis for Cache

```bash
sudo apt install redis-server php8.1-redis
```

Update `.env`:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Enable Gzip in Nginx

Add to nginx config:
```nginx
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;
```

## Resources

- [Sockeon Documentation](https://sockeon.com/v2.0/)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [Nginx WebSocket Proxy](https://nginx.org/en/docs/http/websocket.html)
- [Supervisor Documentation](http://supervisord.org/)

## Support

For issues or questions:
1. Check logs first
2. Review configuration files
3. Verify all services are running
4. Check firewall rules
5. Test WebSocket connection locally

---

**Note:** Replace `yourdomain.com` and `/var/www/your-app` with your actual values throughout this guide.
