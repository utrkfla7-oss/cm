#!/bin/bash

# Netflix Streaming Platform Deployment Script for cPanel
# This script helps deploy the platform to cPanel hosting

set -e

echo "🚀 Netflix Platform Deployment Script"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Configuration
BACKEND_DIR="backend"
DOMAIN=""
SSH_USER=""
SSH_HOST=""
CPANEL_PATH="/home/$SSH_USER/public_html"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --domain)
            DOMAIN="$2"
            shift 2
            ;;
        --ssh-user)
            SSH_USER="$2"
            shift 2
            ;;
        --ssh-host)
            SSH_HOST="$2"
            shift 2
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --domain DOMAIN     Your domain name (e.g., netflix.example.com)"
            echo "  --ssh-user USER     SSH username for cPanel"
            echo "  --ssh-host HOST     SSH hostname for cPanel"
            echo "  --help              Show this help message"
            echo ""
            echo "Example:"
            echo "  $0 --domain netflix.example.com --ssh-user cpaneluser --ssh-host server.example.com"
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Validate required parameters
if [ -z "$DOMAIN" ] || [ -z "$SSH_USER" ] || [ -z "$SSH_HOST" ]; then
    print_error "Missing required parameters"
    echo "Use --help for usage information"
    exit 1
fi

# Check if backend directory exists
check_backend() {
    if [ ! -d "$BACKEND_DIR" ]; then
        print_error "Backend directory not found: $BACKEND_DIR"
        print_error "Please run setup.sh first"
        exit 1
    fi
    
    if [ ! -f "$BACKEND_DIR/.env" ]; then
        print_error "Backend .env file not found"
        print_error "Please configure $BACKEND_DIR/.env first"
        exit 1
    fi
    
    print_success "Backend directory validated"
}

# Test SSH connection
test_ssh_connection() {
    print_status "Testing SSH connection to $SSH_HOST..."
    
    if ssh -o ConnectTimeout=10 "$SSH_USER@$SSH_HOST" "echo 'SSH connection successful'" &> /dev/null; then
        print_success "SSH connection successful"
    else
        print_error "SSH connection failed"
        print_error "Please check your SSH credentials and host"
        exit 1
    fi
}

# Deploy backend
deploy_backend() {
    print_status "Deploying backend to cPanel..."
    
    # Create backend directory on server
    ssh "$SSH_USER@$SSH_HOST" "mkdir -p ~/backend"
    
    # Upload backend files
    print_status "Uploading backend files..."
    rsync -avz --progress "$BACKEND_DIR/" "$SSH_USER@$SSH_HOST:~/backend/"
    
    # Install dependencies on server
    print_status "Installing dependencies on server..."
    ssh "$SSH_USER@$SSH_HOST" "cd ~/backend && npm install --production"
    
    # Create necessary directories
    ssh "$SSH_USER@$SSH_HOST" "cd ~/backend && mkdir -p logs uploads media"
    
    # Set up PM2 or start script
    print_status "Setting up process manager..."
    ssh "$SSH_USER@$SSH_HOST" "cd ~/backend && npm install -g pm2 2>/dev/null || echo 'PM2 already installed or not available'"
    
    # Create start script
    ssh "$SSH_USER@$SSH_HOST" "cat > ~/backend/start.sh << 'EOF'
#!/bin/bash
cd ~/backend
export NODE_ENV=production
node src/index.js
EOF"
    
    ssh "$SSH_USER@$SSH_HOST" "chmod +x ~/backend/start.sh"
    
    print_success "Backend deployed successfully"
}

# Deploy frontend admin panel
deploy_frontend() {
    print_status "Deploying frontend admin panel..."
    
    if [ -d "frontend-admin/build" ]; then
        # Create admin subdirectory
        ssh "$SSH_USER@$SSH_HOST" "mkdir -p $CPANEL_PATH/admin"
        
        # Upload built frontend
        print_status "Uploading frontend build..."
        rsync -avz --progress "frontend-admin/build/" "$SSH_USER@$SSH_HOST:$CPANEL_PATH/admin/"
        
        print_success "Frontend admin panel deployed to /admin"
    else
        print_warning "Frontend build not found. Run 'npm run build' in frontend-admin first."
    fi
}

# Deploy WordPress plugin
deploy_wordpress_plugin() {
    print_status "Preparing WordPress plugin..."
    
    # Create plugin ZIP
    PLUGIN_ZIP="netflix-streaming-plugin.zip"
    zip -r "$PLUGIN_ZIP" wordpress-plugin/
    
    # Upload to server
    scp "$PLUGIN_ZIP" "$SSH_USER@$SSH_HOST:~/"
    
    print_success "WordPress plugin uploaded as ~/$PLUGIN_ZIP"
    print_warning "Please install the plugin manually through WordPress admin"
    
    # Cleanup
    rm "$PLUGIN_ZIP"
}

# Setup database
setup_database() {
    print_status "Database setup instructions..."
    
    echo ""
    echo "Database Setup (Manual Steps):"
    echo "==============================="
    echo "1. Create a MySQL database in cPanel"
    echo "2. Create a database user and assign to the database"
    echo "3. Note the database credentials"
    echo "4. Update backend/.env with database details:"
    echo "   DB_HOST=localhost"
    echo "   DB_NAME=your_database_name"
    echo "   DB_USER=your_database_user"
    echo "   DB_PASSWORD=your_database_password"
    echo ""
}

# Setup SSL and domain
setup_ssl_domain() {
    print_status "SSL and Domain setup..."
    
    echo ""
    echo "Domain and SSL Setup:"
    echo "===================="
    echo "1. Point your domain $DOMAIN to your server IP"
    echo "2. Set up SSL certificate in cPanel (Let's Encrypt recommended)"
    echo "3. Update backend/.env:"
    echo "   ALLOWED_ORIGINS=https://$DOMAIN,https://$DOMAIN/admin"
    echo "4. Update WordPress site URL to https://$DOMAIN"
    echo ""
}

# Create Node.js app in cPanel
setup_nodejs_app() {
    print_status "Node.js app setup instructions..."
    
    echo ""
    echo "cPanel Node.js App Setup:"
    echo "========================"
    echo "1. Go to cPanel > Node.js Apps"
    echo "2. Create New App:"
    echo "   - Node.js version: 16+ (latest available)"
    echo "   - Application mode: Production"
    echo "   - Application root: backend"
    echo "   - Application URL: Leave empty or set subdomain"
    echo "   - Application startup file: src/index.js"
    echo "3. Install dependencies (should be done automatically)"
    echo "4. Start the application"
    echo ""
}

# Final instructions
show_deployment_instructions() {
    echo ""
    echo "🎉 Deployment completed!"
    echo ""
    echo "Next Steps:"
    echo "==========="
    echo ""
    echo "1. Database Configuration:"
    echo "   - Create MySQL database in cPanel"
    echo "   - Update backend/.env with database credentials"
    echo ""
    echo "2. Domain Setup:"
    echo "   - Configure DNS for $DOMAIN"
    echo "   - Set up SSL certificate"
    echo "   - Update environment variables"
    echo ""
    echo "3. Start Backend Service:"
    echo "   - Use cPanel Node.js Apps or PM2"
    echo "   - Ensure backend is running on port 3001"
    echo ""
    echo "4. WordPress Installation:"
    echo "   - Install WordPress if not already installed"
    echo "   - Upload and activate the Netflix plugin"
    echo "   - Configure plugin settings with backend URL"
    echo ""
    echo "5. Admin Panel Access:"
    echo "   - Access at: https://$DOMAIN/admin"
    echo "   - Login with admin credentials"
    echo ""
    echo "6. Testing:"
    echo "   - Test backend API: https://$DOMAIN:3001/health"
    echo "   - Test WordPress frontend: https://$DOMAIN"
    echo "   - Test admin panel: https://$DOMAIN/admin"
    echo ""
    echo "For troubleshooting, check:"
    echo "- Backend logs: ~/backend/logs/"
    echo "- cPanel Error Logs"
    echo "- WordPress Debug Log"
    echo ""
}

# Main execution
main() {
    echo "Starting deployment to cPanel hosting..."
    echo "Domain: $DOMAIN"
    echo "SSH: $SSH_USER@$SSH_HOST"
    echo ""
    
    # Validate and prepare
    check_backend
    test_ssh_connection
    
    # Deploy components
    deploy_backend
    deploy_frontend
    deploy_wordpress_plugin
    
    # Setup instructions
    setup_database
    setup_ssl_domain
    setup_nodejs_app
    
    # Final instructions
    show_deployment_instructions
}

# Run main function
main