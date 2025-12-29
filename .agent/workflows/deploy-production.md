---
description: Deploy Laravel-Sockeon to production with WSS support
---

# Production Deployment Guide for Laravel-Sockeon WebSocket Application

This guide provides step-by-step instructions for deploying your Laravel-Sockeon WebSocket application to a production server with SSL/WSS support using Nginx as a reverse proxy.

## Prerequisites

- Ubuntu 20.04+ or similar Linux distribution
- Root or sudo access
- Domain name pointed to your server
- Basic knowledge of Linux command line

## Step 1: Server Preparation

### 1.1 Update System Packages

```bash
sudo apt update
sudo apt upgrade -y
```

### 1.2 Install Required Software

```bash
# Install PHP 8.1+ and required extensions
sudo apt install -y php8.1-fpm php8.1-cli php8.1-common php8.1-mysql \
    php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml \
    php8.1-bcmath php8.1-intl php8.1-redis

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL/MariaDB
sudo apt install -y mysql-server

# Install Supervisor (for process management)
sudo apt install -y supervisor

# Install Certbot for SSL certificates
sudo apt install -y certbot python3-certbot-nginx
```

## Step 2: Setup Your Laravel Application

### 2.1 Clone Your Repository

```bash
# Create application directory
sudo mkdir -p /var/www/laravel-sockeon
sudo chown -R $USER:$USER /var/www/laravel-sockeon

# Clone your repository
cd /var/www/laravel-sockeon
git clone <your-repo-url> .
```

### 2.2 Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Set proper permissions
sudo chown -R www-data:www-data /var/www/laravel-sockeon
sudo chmod -R 755 /var/www/laravel-sockeon
sudo chmod -R 775 /var/www/laravel-sockeon/storage
sudo chmod -R 775 /var/www/laravel-sockeon/bootstrap/cache
```

### 2.3 Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

Update your `.env` file with production settings:

```env
APP_NAME="Laravel Sockeon Chat"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Sockeon WebSocket Configuration
SOCKEON_HOST=127.0.0.1
SOCKEON_PORT=6001
```

### 2.4 Setup Database

```bash
# Create database
sudo mysql -u root -p

# In MySQL prompt:
CREATE DATABASE your_database;
CREATE USER 'your_username'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON your_database.* TO 'your_username'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 3: Configure Nginx for Laravel + WebSocket

### 3.1 Create Nginx Configuration

Create a new Nginx configuration file:

```bash
sudo nano /etc/nginx/sites-available/laravel-sockeon
```

Add the following configuration:

```nginx
# HTTP Server - Redirects to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect all HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

# HTTPS Server - Laravel Application
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/laravel-sockeon/public;
    index index.php index.html;

    # SSL Configuration (will be added by Certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Laravel application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/laravel-sockeon-access.log;
    error_log /var/log/nginx/laravel-sockeon-error.log;
}

# WebSocket Server (WSS)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ws.yourdomain.com;

    # SSL Configuration (same as main domain)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # WebSocket proxy to Sockeon server
    location / {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        
        # WebSocket support
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Forward proxy headers
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        
        # Timeouts for WebSocket (7 days)
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }

    # Logging
    access_log /var/log/nginx/sockeon-ws-access.log;
    error_log /var/log/nginx/sockeon-ws-error.log;
}
```

### 3.2 Enable Site and Test Configuration

```bash
# Create symbolic link to enable site
sudo ln -s /etc/nginx/sites-available/laravel-sockeon /etc/nginx/sites-enabled/

# Remove default site if exists
sudo rm /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Don't restart yet - we need SSL certificates first
```

## Step 4: Setup SSL Certificates with Let's Encrypt

### 4.1 Obtain SSL Certificates

```bash
# Stop Nginx temporarily
sudo systemctl stop nginx

# Obtain certificates for both domains
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com -d ws.yourdomain.com

# Start Nginx
sudo systemctl start nginx
```

### 4.2 Auto-renewal Setup

```bash
# Test renewal
sudo certbot renew --dry-run

# Certbot automatically sets up a cron job for renewal
# Verify it's scheduled
sudo systemctl list-timers | grep certbot
```

## Step 5: Setup Supervisor for Sockeon WebSocket Server

### 5.1 Create Supervisor Configuration

```bash
sudo nano /etc/supervisor/conf.d/sockeon-websocket.conf
```

Add the following configuration:

```ini
[program:sockeon-websocket]
process_name=%(program_name)s
command=php /var/www/laravel-sockeon/artisan sockeon:serve
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/laravel-sockeon/storage/logs/sockeon-websocket.log
stopwaitsecs=3600
```

### 5.2 Start Supervisor

```bash
# Reload Supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start the WebSocket server
sudo supervisorctl start sockeon-websocket

# Check status
sudo supervisorctl status sockeon-websocket
```

## Step 6: Configure Firewall

```bash
# Allow SSH (if not already allowed)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

## Step 7: Update Client-Side WebSocket Connection

Update your client-side JavaScript to connect to WSS:

```javascript
// In your chat.blade.php or JavaScript file
const socket = new WebSocket('wss://ws.yourdomain.com');

socket.onopen = function(e) {
    console.log('WebSocket connection established');
};

socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Message received:', data);
};

socket.onerror = function(error) {
    console.error('WebSocket error:', error);
};

socket.onclose = function(event) {
    console.log('WebSocket connection closed');
};
```

## Step 8: Testing and Verification

### 8.1 Test Laravel Application

```bash
# Visit your domain
https://yourdomain.com
```

### 8.2 Test WebSocket Connection

```bash
# Check if WebSocket server is running
sudo supervisorctl status sockeon-websocket

# Check WebSocket logs
tail -f /var/www/laravel-sockeon/storage/logs/sockeon-websocket.log

# Test WebSocket connection from browser console
# Open https://yourdomain.com and check browser console for WebSocket connection
```

### 8.3 Monitor Nginx Logs

```bash
# Watch access logs
sudo tail -f /var/log/nginx/laravel-sockeon-access.log

# Watch WebSocket logs
sudo tail -f /var/log/nginx/sockeon-ws-access.log

# Watch error logs
sudo tail -f /var/log/nginx/laravel-sockeon-error.log
sudo tail -f /var/log/nginx/sockeon-ws-error.log
```

## Step 9: Maintenance and Monitoring

### 9.1 Useful Supervisor Commands

```bash
# Restart WebSocket server
sudo supervisorctl restart sockeon-websocket

# Stop WebSocket server
sudo supervisorctl stop sockeon-websocket

# View logs
sudo supervisorctl tail -f sockeon-websocket

# Restart all supervised processes
sudo supervisorctl restart all
```

### 9.2 Useful Nginx Commands

```bash
# Reload Nginx (without dropping connections)
sudo nginx -s reload

# Restart Nginx
sudo systemctl restart nginx

# Check Nginx status
sudo systemctl status nginx
```

### 9.3 Application Updates

```bash
# Pull latest changes
cd /var/www/laravel-sockeon
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

# Restart WebSocket server
sudo supervisorctl restart sockeon-websocket
```

## Troubleshooting

### WebSocket Connection Fails

1. **Check if Sockeon server is running:**
   ```bash
   sudo supervisorctl status sockeon-websocket
   ```

2. **Check WebSocket logs:**
   ```bash
   tail -f /var/www/laravel-sockeon/storage/logs/sockeon-websocket.log
   ```

3. **Verify Nginx is proxying correctly:**
   ```bash
   sudo tail -f /var/log/nginx/sockeon-ws-error.log
   ```

4. **Test local WebSocket connection:**
   ```bash
   # Install wscat for testing
   npm install -g wscat
   
   # Test local connection
   wscat -c ws://127.0.0.1:6001
   ```

### SSL Certificate Issues

1. **Verify certificates are valid:**
   ```bash
   sudo certbot certificates
   ```

2. **Renew certificates manually:**
   ```bash
   sudo certbot renew
   sudo systemctl reload nginx
   ```

### Permission Issues

```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/laravel-sockeon/storage
sudo chmod -R 775 /var/www/laravel-sockeon/storage
```

### High Memory Usage

Monitor and adjust PHP-FPM settings:

```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf

# Adjust these values based on your server resources:
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

## Security Best Practices

1. **Keep system updated:**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Use strong database passwords**

3. **Disable root SSH login:**
   ```bash
   sudo nano /etc/ssh/sshd_config
   # Set: PermitRootLogin no
   sudo systemctl restart sshd
   ```

4. **Enable fail2ban:**
   ```bash
   sudo apt install fail2ban
   sudo systemctl enable fail2ban
   sudo systemctl start fail2ban
   ```

5. **Regular backups:**
   - Database backups
   - Application files
   - SSL certificates

## Performance Optimization

### Enable Gzip Compression in Nginx

Add to your Nginx configuration:

```nginx
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;
```

### Enable OPcache

```bash
sudo nano /etc/php/8.1/fpm/php.ini

# Add or modify:
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

## Scaling Considerations

For high-traffic applications:

1. **Use Redis for sessions and cache:**
   ```bash
   sudo apt install redis-server
   ```
   
   Update `.env`:
   ```env
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

2. **Use Queue Workers:**
   ```bash
   # Add to supervisor
   sudo nano /etc/supervisor/conf.d/laravel-worker.conf
   ```

3. **Load Balancing:**
   - Use multiple WebSocket server instances
   - Implement sticky sessions in Nginx
   - Use Redis for pub/sub between instances

## Monitoring

### Setup Monitoring Tools

```bash
# Install htop for process monitoring
sudo apt install htop

# Install netdata for comprehensive monitoring
bash <(curl -Ss https://my-netdata.io/kickstart.sh)
```

### Log Rotation

Laravel handles its own log rotation, but ensure Nginx logs are rotated:

```bash
sudo nano /etc/logrotate.d/nginx

# Verify configuration includes:
/var/log/nginx/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    prerotate
        if [ -d /etc/logrotate.d/httpd-prerotate ]; then \
            run-parts /etc/logrotate.d/httpd-prerotate; \
        fi
    endscript
    postrotate
        invoke-rc.d nginx rotate >/dev/null 2>&1
    endscript
}
```

## Additional Resources

- [Sockeon Documentation](https://sockeon.com/v2.0/)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Nginx WebSocket Proxying](https://nginx.org/en/docs/http/websocket.html)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [Supervisor Documentation](http://supervisord.org/)

---

**Note:** Replace `yourdomain.com` with your actual domain name throughout this guide.
