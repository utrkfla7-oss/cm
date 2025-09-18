#!/bin/bash

# Netflix Streaming Platform Setup Script for cPanel
# This script helps set up the full Netflix-style streaming platform

set -e

echo "🎬 Netflix Streaming Platform Setup"
echo "===================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKEND_DIR="backend"
FRONTEND_DIR="frontend-admin"
WORDPRESS_DIR="wordpress-plugin"
MOBILE_DIR="mobile-app"

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

# Check if Node.js is installed
check_nodejs() {
    print_status "Checking Node.js installation..."
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node --version)
        print_success "Node.js is installed: $NODE_VERSION"
        
        # Check if version is >= 16
        NODE_MAJOR_VERSION=$(echo $NODE_VERSION | cut -d'.' -f1 | sed 's/v//')
        if [ "$NODE_MAJOR_VERSION" -ge 16 ]; then
            print_success "Node.js version is compatible"
        else
            print_error "Node.js version must be 16 or higher. Current: $NODE_VERSION"
            exit 1
        fi
    else
        print_error "Node.js is not installed. Please install Node.js 16+ first."
        echo "Download from: https://nodejs.org/"
        exit 1
    fi
}

# Check if npm is installed
check_npm() {
    print_status "Checking npm installation..."
    if command -v npm &> /dev/null; then
        NPM_VERSION=$(npm --version)
        print_success "npm is installed: $NPM_VERSION"
    else
        print_error "npm is not installed. Please install npm first."
        exit 1
    fi
}

# Setup backend
setup_backend() {
    print_status "Setting up backend..."
    
    if [ -d "$BACKEND_DIR" ]; then
        cd "$BACKEND_DIR"
        
        # Install dependencies
        print_status "Installing backend dependencies..."
        npm install
        
        # Copy environment file
        if [ ! -f ".env" ]; then
            if [ -f ".env.example" ]; then
                cp .env.example .env
                print_warning "Created .env file from .env.example"
                print_warning "Please configure your environment variables in backend/.env"
            else
                print_error ".env.example not found"
            fi
        else
            print_success "Backend .env already exists"
        fi
        
        # Create logs directory
        mkdir -p logs
        
        # Create media directories
        mkdir -p uploads media
        
        cd ..
        print_success "Backend setup completed"
    else
        print_error "Backend directory not found: $BACKEND_DIR"
        exit 1
    fi
}

# Setup frontend admin panel
setup_frontend() {
    print_status "Setting up frontend admin panel..."
    
    if [ -d "$FRONTEND_DIR" ]; then
        cd "$FRONTEND_DIR"
        
        # Install dependencies
        print_status "Installing frontend dependencies..."
        npm install
        
        # Create environment file
        if [ ! -f ".env" ]; then
            cat > .env << EOF
REACT_APP_API_URL=http://localhost:3001/api
REACT_APP_NAME=Netflix Admin Panel
EOF
            print_warning "Created .env file for frontend"
            print_warning "Please configure REACT_APP_API_URL in frontend-admin/.env"
        else
            print_success "Frontend .env already exists"
        fi
        
        cd ..
        print_success "Frontend setup completed"
    else
        print_error "Frontend directory not found: $FRONTEND_DIR"
        exit 1
    fi
}

# Setup WordPress plugin
setup_wordpress() {
    print_status "Setting up WordPress plugin..."
    
    if [ -d "$WORDPRESS_DIR" ]; then
        print_success "WordPress plugin is ready for upload"
        print_warning "To install:"
        echo "  1. Zip the wordpress-plugin directory"
        echo "  2. Upload via WordPress admin: Plugins > Add New > Upload Plugin"
        echo "  3. Activate the plugin"
        echo "  4. Configure backend URL and API key in plugin settings"
    else
        print_error "WordPress plugin directory not found: $WORDPRESS_DIR"
        exit 1
    fi
}

# Setup mobile app
setup_mobile() {
    print_status "Setting up mobile app..."
    
    if [ -d "$MOBILE_DIR" ]; then
        print_status "Mobile app directory found"
        print_warning "Flutter mobile app setup requires separate Flutter installation"
        print_warning "Please refer to mobile-app/README.md for setup instructions"
    else
        print_warning "Mobile app directory not found: $MOBILE_DIR"
        print_warning "Mobile app setup will be skipped"
    fi
}

# Create production build
build_production() {
    print_status "Building production assets..."
    
    # Build backend (if needed)
    cd "$BACKEND_DIR"
    print_status "Backend is ready for production"
    cd ..
    
    # Build frontend
    if [ -d "$FRONTEND_DIR" ]; then
        cd "$FRONTEND_DIR"
        print_status "Building frontend for production..."
        npm run build
        print_success "Frontend built successfully"
        cd ..
    fi
}

# Create ZIP file for easy deployment
create_deployment_package() {
    print_status "Creating deployment package..."
    
    TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    PACKAGE_NAME="netflix-platform-${TIMESTAMP}.zip"
    
    # Create temporary directory
    TEMP_DIR="netflix-platform-deploy"
    mkdir -p "$TEMP_DIR"
    
    # Copy necessary files
    cp -r "$BACKEND_DIR" "$TEMP_DIR/"
    cp -r "$WORDPRESS_DIR" "$TEMP_DIR/"
    
    # Copy frontend build if exists
    if [ -d "$FRONTEND_DIR/build" ]; then
        cp -r "$FRONTEND_DIR/build" "$TEMP_DIR/frontend-build"
    fi
    
    # Copy documentation
    if [ -d "docs" ]; then
        cp -r docs "$TEMP_DIR/"
    fi
    
    # Copy setup scripts
    cp scripts/*.sh "$TEMP_DIR/" 2>/dev/null || true
    
    # Create README for deployment
    cat > "$TEMP_DIR/README.txt" << EOF
Netflix Streaming Platform Deployment Package
============================================

This package contains all necessary files for deploying the Netflix streaming platform.

Contents:
- backend/          - Node.js backend application
- wordpress-plugin/ - WordPress plugin for frontend
- frontend-build/   - Built React admin panel (if available)
- docs/            - Documentation
- deploy.sh        - Deployment script

Setup Instructions:
1. Upload backend/ to your server
2. Install WordPress plugin
3. Configure environment variables
4. Start the backend service

For detailed instructions, see docs/deployment/README.md
EOF
    
    # Create ZIP file
    zip -r "$PACKAGE_NAME" "$TEMP_DIR"
    
    # Cleanup
    rm -rf "$TEMP_DIR"
    
    print_success "Deployment package created: $PACKAGE_NAME"
}

# Display final instructions
show_final_instructions() {
    echo ""
    echo "🎉 Setup completed successfully!"
    echo ""
    echo "Next Steps:"
    echo "==========="
    echo ""
    echo "1. Backend Configuration:"
    echo "   - Edit backend/.env with your database and API keys"
    echo "   - Ensure MySQL database is created"
    echo "   - Start backend: cd backend && npm start"
    echo ""
    echo "2. WordPress Plugin:"
    echo "   - Zip the wordpress-plugin directory"
    echo "   - Upload and activate in WordPress admin"
    echo "   - Configure backend URL in plugin settings"
    echo ""
    echo "3. Admin Panel:"
    echo "   - Edit frontend-admin/.env with correct API URL"
    echo "   - Build: cd frontend-admin && npm run build"
    echo "   - Deploy build/ directory to web server"
    echo ""
    echo "4. cPanel Deployment:"
    echo "   - Use the created deployment package"
    echo "   - Follow docs/deployment/cpanel-guide.md"
    echo ""
    echo "For support, visit: https://github.com/utrkfla7-oss/cm"
}

# Main execution
main() {
    echo "Starting Netflix Streaming Platform setup..."
    echo ""
    
    # Check prerequisites
    check_nodejs
    check_npm
    
    # Setup components
    setup_backend
    setup_frontend
    setup_wordpress
    setup_mobile
    
    # Build for production
    if [ "$1" = "--production" ]; then
        build_production
        create_deployment_package
    fi
    
    # Show final instructions
    show_final_instructions
}

# Run main function with all arguments
main "$@"