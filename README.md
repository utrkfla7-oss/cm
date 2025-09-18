# Netflix Streaming Platform

A full-featured Netflix-style video streaming platform with WordPress integration, built for deployment on cPanel hosting.

## 🎬 Features

### Core Platform
- **Netflix-style UI/UX** with dark theme and responsive design
- **Video streaming** with HLS/DASH adaptive bitrate support
- **Multi-language subtitles** with English→Bengali translation
- **User management** with subscription tiers (Free, Basic, Premium)
- **Content management** for movies, TV shows, and episodes
- **Admin dashboard** with analytics and system monitoring

### Backend Services
- **Node.js API** optimized for cPanel hosting
- **Video transcoding** with FFmpeg integration
- **IMDb/TMDb integration** for metadata import
- **Batch import** functionality for popular content
- **User authentication** with JWT tokens
- **Subscription management** with payment integration ready
- **File upload** with size and type restrictions

### WordPress Integration
- **Custom post types** for movies and TV shows
- **Advanced shortcodes** for video player and content display
- **Admin interface** for content management
- **User subscription** integration
- **SEO optimization** for content discovery

### Admin Panel
- **React-based dashboard** with real-time updates
- **Content management** interface
- **User administration** with role management
- **Import management** with progress tracking
- **System monitoring** and analytics
- **Settings configuration** for all services

### Mobile Support
- **Flutter app** for Android and iOS (setup included)
- **Responsive web** interface for mobile browsers
- **Progressive Web App** capabilities

## 🚀 Quick Start

### Prerequisites
- Node.js 16+ 
- MySQL 5.7+
- WordPress 5.0+
- cPanel hosting account
- FFmpeg (optional, for local transcoding)

### Installation

1. **Clone and Setup**
```bash
git clone https://github.com/utrkfla7-oss/cm.git
cd cm
chmod +x scripts/setup.sh
./scripts/setup.sh
```

2. **Configure Environment**
```bash
# Edit backend configuration
cp backend/.env.example backend/.env
nano backend/.env

# Edit frontend configuration  
cp frontend-admin/.env.example frontend-admin/.env
nano frontend-admin/.env
```

3. **Database Setup**
```bash
# Create MySQL database
mysql -u root -p
CREATE DATABASE netflix_streaming;
CREATE USER 'netflix_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON netflix_streaming.* TO 'netflix_user'@'localhost';
FLUSH PRIVILEGES;
```

4. **Start Services**
```bash
# Start backend
cd backend
npm start

# Start frontend (development)
cd ../frontend-admin
npm start
```

5. **Install WordPress Plugin**
- Zip the `wordpress-plugin` directory
- Upload via WordPress admin: Plugins → Add New → Upload Plugin
- Activate and configure with backend URL

## 📦 Deployment to cPanel

### Automated Deployment
```bash
# Build production package
./scripts/setup.sh --production

# Deploy to cPanel
./scripts/deploy.sh --domain your-domain.com --ssh-user cpanel_user --ssh-host your-server.com
```

### Manual cPanel Setup

1. **Upload Backend**
   - Upload `backend/` to your server
   - Install dependencies: `npm install --production`
   - Create Node.js app in cPanel

2. **Configure Database**
   - Create MySQL database in cPanel
   - Update `.env` with database credentials

3. **Upload WordPress Plugin**
   - Install WordPress if not already installed
   - Upload and activate the Netflix plugin
   - Configure backend URL in plugin settings

4. **Deploy Admin Panel**
   - Build: `cd frontend-admin && npm run build`
   - Upload `build/` contents to `/admin` subdirectory

## 🔧 Configuration

### Backend Environment (.env)
```env
# Server
NODE_ENV=production
PORT=3001

# Database
DB_HOST=localhost
DB_NAME=netflix_streaming
DB_USER=your_db_user
DB_PASSWORD=your_db_password

# JWT
JWT_SECRET=your-super-secret-key

# API Keys
TMDB_API_KEY=your_tmdb_api_key
OPENAI_API_KEY=your_openai_key

# File Uploads
MAX_FILE_SIZE=5368709120
UPLOAD_DIR=./uploads
MEDIA_DIR=./media

# Security
ALLOWED_ORIGINS=https://yourdomain.com
API_KEY_WP_INTEGRATION=your-wordpress-api-key
```

### WordPress Plugin Configuration
```php
// In WordPress admin: Netflix Platform → Settings
Backend URL: https://yourdomain.com:3001
API Key: your-wordpress-api-key
Auto Publish: Enable/Disable
Default Quality: 720p
Enable Subtitles: Yes
Subtitle Languages: en,bn
```

## 🎮 Usage

### Content Management

1. **Import from IMDb/TMDb**
   - Go to Import Manager in admin panel
   - Search for content or use batch import
   - Monitor import progress

2. **Upload Videos**
   - Use Video Upload in admin panel
   - Videos are automatically transcoded to multiple qualities
   - HLS streams are generated for adaptive bitrate

3. **WordPress Integration**
   - Content automatically syncs to WordPress
   - Use shortcodes to display content:
   ```php
   [netflix_player id="123" type="movie"]
   [netflix_movies limit="12" genre="action"]
   [netflix_shows layout="carousel"]
   ```

### User Management

1. **Subscription Tiers**
   - Free: Limited content, ads
   - Basic: HD content, fewer ads
   - Premium: Full library, no ads, offline viewing

2. **User Roles**
   - Admin: Full access
   - Netflix Manager: Content management
   - User: Content viewing

### Mobile App

1. **Flutter Setup**
```bash
cd mobile-app
flutter pub get
flutter run
```

2. **Build for Release**
```bash
# Android
flutter build apk --release

# iOS
flutter build ios --release
```

## 🔒 Security Features

- **JWT Authentication** with secure tokens
- **Role-based Access Control** for different user types
- **Rate Limiting** to prevent abuse
- **Input Validation** and sanitization
- **CORS Protection** for API endpoints
- **DRM Support** for premium content
- **File Upload Restrictions** by size and type

## 📊 Analytics & Monitoring

- **Real-time Dashboard** with key metrics
- **User Analytics** tracking watch history
- **Content Performance** monitoring
- **System Health** monitoring
- **Import Job** progress tracking
- **Error Logging** and reporting

## 🌐 Multi-language Support

- **Interface Translation** ready (WordPress standards)
- **Subtitle Translation** English → Bengali with AI
- **Content Localization** support
- **RTL Language** support ready

## 🔧 Customization

### Themes
- **Dark Theme** (default Netflix-style)
- **Custom CSS** support
- **Responsive Design** for all devices

### Player Features
- **Adaptive Bitrate** streaming
- **Multiple Audio Tracks** support
- **Subtitle Toggle** with styling options
- **Playback Speed** control
- **Fullscreen Mode** support
- **Chromecast Support** ready

## 📱 Mobile App Features

- **Native Video Player** with HLS support
- **Offline Download** capability
- **Push Notifications** for new content
- **User Authentication** sync
- **Multi-language** interface

## 🛠️ Development

### Backend Development
```bash
cd backend
npm run dev  # Start with nodemon
npm test     # Run tests
npm run lint # Check code style
```

### Frontend Development
```bash
cd frontend-admin
npm start    # Start development server
npm test     # Run tests
npm run build # Build for production
```

### WordPress Development
- Plugin follows WordPress coding standards
- Hooks and filters for customization
- Translation ready with .pot file

## 🐛 Troubleshooting

### Common Issues

1. **Video not playing**
   - Check transcoding status
   - Verify file permissions
   - Check browser console for errors

2. **Backend connection failed**
   - Verify backend is running
   - Check firewall settings
   - Confirm API key configuration

3. **Import jobs failing**
   - Check TMDb API key
   - Verify internet connectivity
   - Check server memory limits

### Support

- **Documentation**: `/docs` directory
- **Logs**: Check `backend/logs/` for errors
- **GitHub Issues**: Report bugs and feature requests

## 📄 License

MIT License - see LICENSE file for details

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📞 Support

For support and questions:
- GitHub Issues: https://github.com/utrkfla7-oss/cm/issues
- Documentation: See `/docs` directory
- Email: support@yournetflixplatform.com

---

**Built with ❤️ for the streaming community**