#!/bin/bash

#############################################
# Laravel-Sockeon Deployment Script
# This script automates the deployment process
#############################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/laravel-sockeon"
BRANCH="main"

echo -e "${GREEN}Starting Laravel-Sockeon deployment...${NC}"

# Navigate to application directory
cd $APP_DIR

# Put application in maintenance mode
echo -e "${YELLOW}Putting application in maintenance mode...${NC}"
php artisan down || true

# Pull latest changes
echo -e "${YELLOW}Pulling latest changes from Git...${NC}"
git pull origin $BRANCH

# Install/Update Composer dependencies
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
composer install --optimize-autoloader --no-dev

# Run database migrations
echo -e "${YELLOW}Running database migrations...${NC}"
php artisan migrate --force

# Clear and rebuild cache
echo -e "${YELLOW}Clearing and rebuilding cache...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo -e "${YELLOW}Setting proper permissions...${NC}"
sudo chown -R www-data:www-data $APP_DIR/storage
sudo chown -R www-data:www-data $APP_DIR/bootstrap/cache
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

# Restart WebSocket server
echo -e "${YELLOW}Restarting WebSocket server...${NC}"
sudo supervisorctl restart sockeon-websocket

# Reload PHP-FPM
echo -e "${YELLOW}Reloading PHP-FPM...${NC}"
sudo systemctl reload php8.1-fpm

# Bring application back online
echo -e "${YELLOW}Bringing application back online...${NC}"
php artisan up

echo -e "${GREEN}Deployment completed successfully!${NC}"

# Show WebSocket server status
echo -e "${YELLOW}WebSocket server status:${NC}"
sudo supervisorctl status sockeon-websocket
