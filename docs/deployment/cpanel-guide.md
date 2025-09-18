# Netflix Streaming Platform - cPanel Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the Netflix Streaming Platform on cPanel shared hosting.

## Prerequisites

### Server Requirements
- **cPanel hosting** with Node.js support
- **MySQL 5.7+** database
- **PHP 8.0+** for WordPress
- **SSH access** (recommended)
- **Domain with SSL** certificate

### Local Requirements
- **Node.js 16+** installed locally
- **Git** for version control
- **ZIP utility** for file compression

## Step 1: Initial Setup

### 1.1 Download and Prepare
```bash
# Clone the repository
git clone https://github.com/utrkfla7-oss/cm.git
cd cm

# Run setup script
chmod +x scripts/setup.sh
./scripts/setup.sh --production
```

This will:
- Install all dependencies
- Create production builds
- Generate deployment package
- Validate all components

### 1.2 Configure Environment Variables

#### Backend Configuration
Edit `backend/.env`:
```env
# Production Settings
NODE_ENV=production
PORT=3001

# Database (Update with your cPanel MySQL details)
DB_HOST=localhost
DB_NAME=your_cpanel_database_name
DB_USER=your_cpanel_db_user
DB_PASSWORD=your_cpanel_db_password

# Security
JWT_SECRET=your-super-secret-jwt-key-change-this
ALLOWED_ORIGINS=https://yourdomain.com,https://yourdomain.com/admin

# API Keys (Get from respective services)
TMDB_API_KEY=your_tmdb_api_key
OPENAI_API_KEY=your_openai_api_key

# WordPress Integration
API_KEY_WP_INTEGRATION=your-wordpress-api-key

# File Upload Settings for cPanel
MAX_FILE_SIZE=1073741824
UPLOAD_DIR=./uploads
MEDIA_DIR=./media

# cPanel Optimizations
CPANEL_COMPATIBLE=true
SINGLE_PROCESS_MODE=true
```

## Step 2: cPanel Database Setup

### 2.1 Create MySQL Database
1. Login to cPanel
2. Go to **MySQL Databases**
3. Create new database: `your_account_netflix`
4. Create database user with strong password
5. Add user to database with ALL PRIVILEGES

### 2.2 Note Database Details
```
Database Name: youraccount_netflix
Database User: youraccount_netflix_user
Database Password: [your chosen password]
Database Host: localhost
```

## Step 3: Backend Deployment

### 3.1 Upload Backend Files

#### Option A: SSH Upload (Recommended)
```bash
# Upload via rsync
rsync -avz backend/ user@yourserver.com:~/backend/

# Or use the deploy script
./scripts/deploy.sh --domain yourdomain.com --ssh-user cpaneluser --ssh-host yourserver.com
```

#### Option B: File Manager Upload
1. Zip the `backend` directory
2. Upload via cPanel File Manager
3. Extract in your home directory (`/home/youraccount/backend/`)

### 3.2 Install Dependencies
```bash
# SSH into your server
ssh youraccount@yourserver.com

# Navigate to backend directory
cd ~/backend

# Install production dependencies
npm install --production

# Create necessary directories
mkdir -p logs uploads media
```

### 3.3 Setup Node.js Application in cPanel

1. Go to cPanel → **Node.js Apps**
2. Click **Create App**
3. Configure:
   - **Node.js version**: 16.x or later
   - **Application mode**: Production
   - **Application root**: `backend`
   - **Application URL**: Leave empty (API only)
   - **Application startup file**: `src/index.js`
4. Click **Create**

### 3.4 Configure Environment Variables in cPanel
1. In Node.js Apps, click your app
2. Add environment variables from your `.env` file
3. Restart the application

## Step 4: WordPress Setup

### 4.1 Install WordPress (if not already installed)
1. Use cPanel **WordPress Installer** or
2. Download and install manually

### 4.2 Install Netflix Plugin
1. Zip the `wordpress-plugin` directory
2. Go to WordPress admin → **Plugins** → **Add New** → **Upload Plugin**
3. Upload and activate the plugin

### 4.3 Configure Plugin
1. Go to **Netflix Platform** → **Settings**
2. Configure:
   ```
   Backend URL: https://yourdomain.com:3001
   API Key: your-wordpress-api-key (same as in backend .env)
   Auto Publish: Enable
   Default Quality: 720p
   Enable Subtitles: Yes
   Subtitle Languages: en,bn
   ```

## Step 5: Frontend Admin Panel

### 5.1 Build Frontend
```bash
# On your local machine
cd frontend-admin
npm run build
```

### 5.2 Upload Admin Panel
```bash
# Create admin directory on server
ssh youraccount@yourserver.com "mkdir -p ~/public_html/admin"

# Upload build files
rsync -avz frontend-admin/build/ youraccount@yourserver.com:~/public_html/admin/
```

## Step 6: SSL and Security Setup

### 6.1 Enable SSL
1. Go to cPanel → **SSL/TLS**
2. Enable **Let's Encrypt** for your domain
3. Force HTTPS redirects

### 6.2 Configure Firewall (if available)
- Allow port 3001 for Node.js app
- Block direct access to sensitive directories

## Step 7: Testing and Verification

### 7.1 Test Backend API
```bash
# Test health endpoint
curl https://yourdomain.com:3001/health

# Should return: {"status":"OK","timestamp":"...","version":"1.0.0"}
```

### 7.2 Test WordPress Integration
1. Visit your WordPress site
2. Create a test post with `[netflix_player id="1"]` shortcode
3. Verify the player loads correctly

### 7.3 Test Admin Panel
1. Visit `https://yourdomain.com/admin`
2. Login with admin credentials
3. Verify dashboard loads and shows backend connection

## Step 8: Content Import and Setup

### 8.1 Import Sample Content
1. Login to admin panel
2. Go to **Import Manager**
3. Import popular movies/shows from TMDb

### 8.2 Upload Video Content
1. Use **Video Upload** in admin panel
2. Monitor transcoding progress
3. Test video playback

## Troubleshooting

### Common Issues

#### Backend Not Starting
```bash
# Check Node.js app logs in cPanel
# Verify environment variables
# Check database connection

# Manual restart
cd ~/backend
node src/index.js
```

#### Database Connection Failed
- Verify database credentials in `.env`
- Check database user privileges
- Ensure database exists

#### Video Upload Issues
- Check file size limits in cPanel
- Verify upload directory permissions
- Monitor disk space usage

#### WordPress Plugin Errors
- Check plugin error logs
- Verify backend URL is accessible
- Check API key configuration

### Performance Optimization

#### cPanel Optimizations
```env
# In backend/.env
CPANEL_COMPATIBLE=true
SINGLE_PROCESS_MODE=true
RATE_LIMIT_MAX_REQUESTS=50
```

#### Database Optimization
- Use database indexing
- Enable query caching
- Monitor slow queries

#### File Storage
- Use CDN for media files (optional)
- Implement file compression
- Regular cleanup of temporary files

## Security Checklist

- [ ] SSL certificate installed and forced
- [ ] Strong database passwords
- [ ] JWT secret changed from default
- [ ] API keys secured
- [ ] File upload restrictions in place
- [ ] CORS properly configured
- [ ] Regular backups scheduled

## Maintenance

### Regular Tasks
- Monitor disk space usage
- Update Node.js dependencies
- WordPress and plugin updates
- Database optimization
- Log rotation

### Backup Strategy
```bash
# Database backup
mysqldump -h localhost -u dbuser -p dbname > backup.sql

# File backup
tar -czf backup.tar.gz backend/ public_html/
```

## Support

### Log Locations
- **Backend logs**: `~/backend/logs/`
- **cPanel logs**: cPanel → Error Logs
- **WordPress logs**: `wp-content/debug.log`

### Performance Monitoring
- Use cPanel resource usage monitor
- Monitor Node.js app performance
- Check MySQL process list

### Getting Help
- Check documentation in `/docs` directory
- Review GitHub issues
- Contact hosting provider for server-specific issues

---

**Deployment Complete! 🎉**

Your Netflix Streaming Platform should now be live at:
- **Main Site**: https://yourdomain.com
- **Admin Panel**: https://yourdomain.com/admin
- **API Health**: https://yourdomain.com:3001/health