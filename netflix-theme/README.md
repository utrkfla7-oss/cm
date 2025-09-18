# Netflix Streaming Platform WordPress Theme

A professional, production-ready WordPress theme that transforms your website into a Netflix-style streaming platform. Perfect for video streaming services, entertainment websites, movie databases, and content creators.

## 🎬 Features

### **Complete Streaming Platform**
- Netflix-style dark UI with responsive design
- Advanced video player with HLS/DASH streaming support
- Multi-quality video streaming (240p - 1080p)
- Adaptive bitrate streaming for optimal viewing experience
- Multi-language subtitle support with AI-powered translations
- Chromecast and AirPlay support

### **Content Management**
- Custom post types for Movies, TV Shows, and Episodes
- Advanced meta fields for video details, cast, directors, ratings
- Taxonomies for genres, release years, and content ratings
- Featured image support with multiple image sizes
- Bulk import from TMDb/IMDb APIs
- Content scheduling and publishing workflow

### **User Experience**
- Netflix-style homepage with hero sections and content rows
- Personalized "My List" functionality
- Continue watching feature with progress tracking
- Advanced search with real-time results
- User ratings and reviews system
- Subscription-based access control (Free, Basic, Premium)

### **Backend Integration**
- REST API endpoints for external backend integration
- Real-time content synchronization
- Analytics and view tracking
- User subscription management
- Video upload and transcoding support

### **Mobile Optimized**
- Fully responsive design for all devices
- Touch-friendly video controls
- Mobile-specific navigation and layouts
- Progressive Web App (PWA) capabilities
- Offline content support (with backend)

## 🚀 Quick Start

### **Installation**

1. **Download the theme ZIP file**
2. **Upload to WordPress:**
   - Go to WordPress Admin → Appearance → Themes
   - Click "Add New" → "Upload Theme"
   - Select the netflix-theme.zip file
   - Click "Install Now" and "Activate"

3. **Configure the theme:**
   - Go to Appearance → Customize → Netflix Settings
   - Set your backend API URL (if using external backend)
   - Configure API keys for TMDb, OpenAI, etc.

### **Basic Setup**

1. **Create sample content:**
   ```
   - Add new Movie (Posts → Movies → Add New)
   - Add new TV Show (Posts → TV Shows → Add New)
   - Add episodes for TV shows
   - Set featured images and fill in movie/show details
   ```

2. **Configure menus:**
   ```
   - Go to Appearance → Menus
   - Create Primary Navigation menu
   - Add pages like Home, Movies, TV Shows, Genres
   - Assign to "Primary Navigation" location
   ```

3. **Set homepage:**
   ```
   - Go to Settings → Reading
   - Set front page to "Static page"
   - Create a page using Netflix homepage template
   ```

## 📋 Theme Structure

```
netflix-theme/
├── style.css                 # Main theme stylesheet
├── index.php                 # Main template file
├── functions.php             # Theme functions and setup
├── header.php               # Header template
├── footer.php               # Footer template
├── single-movie.php         # Movie detail page
├── single-tv_show.php       # TV show detail page
├── single-episode.php       # Episode detail page
├── archive-movie.php        # Movies archive page
├── archive-tv_show.php      # TV shows archive page
├── search.php               # Search results page
├── 404.php                  # 404 error page
├── assets/
│   ├── css/
│   │   ├── video-player.css # Video player styles
│   │   └── components.css   # Component styles
│   ├── js/
│   │   ├── main.js         # Main theme JavaScript
│   │   └── video-player.js # Video player functionality
│   └── images/             # Theme images and icons
├── inc/
│   ├── shortcodes.php      # Netflix shortcodes
│   ├── backend-integration.php # API integration
│   ├── admin-functions.php # Admin panel functions
│   ├── theme-options.php   # Theme customizer options
│   └── subscription-management.php # User subscriptions
└── template-parts/          # Reusable template parts
```

## 🎯 Shortcodes

### **Video Player**
```php
[netflix_player id="123" width="100%" height="500px"]
[netflix_player url="video.mp4" poster="poster.jpg" subtitles='[{"src":"en.vtt","lang":"en","label":"English"}]']
```

### **Content Grids**
```php
[netflix_movies limit="12" genre="action,thriller" columns="4"]
[netflix_tv_shows limit="8" featured="1" orderby="date"]
```

### **Content Sliders**
```php
[netflix_slider title="Trending Now" type="both" limit="20"]
[netflix_slider title="Action Movies" type="movie" category="action"]
```

## ⚙️ Configuration

### **Theme Customizer Options**

Access via **Appearance → Customize → Netflix Settings**:

- **Backend API URL**: URL of your backend server
- **TMDb API Key**: For movie/TV show data import
- **OpenAI API Key**: For subtitle translations
- **Streaming Settings**: Video quality, player options
- **Subscription Plans**: Configure pricing and features

### **Required Plugins**

While the theme works standalone, these plugins enhance functionality:

- **Advanced Custom Fields (ACF)**: Enhanced meta fields
- **WP REST API**: Better API functionality
- **Yoast SEO**: SEO optimization
- **WP Rocket**: Performance optimization

## 🔧 Backend Integration

The theme supports integration with the included Node.js backend:

### **API Endpoints**

```javascript
// Content synchronization
POST /api/wp/sync

// Get streaming URLs
POST /api/videos/stream

// Import from TMDb
POST /api/imdb/import

// User subscriptions
GET/POST /api/wp/subscription
```

### **Setting up Backend**

1. **Configure backend URL in theme customizer**
2. **Set API authentication keys**
3. **Test connection in WordPress admin**

## 🎨 Customization

### **Colors and Branding**

Edit `style.css` CSS variables:

```css
:root {
    --netflix-red: #e50914;
    --netflix-dark: #141414;
    --netflix-gray: #333;
    --netflix-white: #ffffff;
    --netflix-bg: #000000;
}
```

### **Custom Templates**

Create custom page templates:

```php
<?php
/*
Template Name: Custom Netflix Page
*/
get_header();
// Your custom content
get_footer();
?>
```

### **Child Theme**

Create a child theme for custom modifications:

```php
// child-theme/functions.php
<?php
add_action('wp_enqueue_scripts', 'child_theme_styles');
function child_theme_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}
?>
```

## 📱 Mobile App Integration

The theme includes API endpoints for mobile app integration:

### **Flutter App Setup**

1. **Configure API base URL in mobile app**
2. **Set authentication tokens**
3. **Test video streaming URLs**
4. **Configure push notifications**

### **API Authentication**

```javascript
// Example API call from mobile app
fetch('https://yoursite.com/wp-json/netflix/v1/content/123', {
    headers: {
        'Authorization': 'Bearer ' + userToken,
        'Content-Type': 'application/json'
    }
})
```

## 🔒 Security & Performance

### **Security Features**

- JWT token authentication
- Input sanitization and validation
- SQL injection prevention
- XSS protection
- Rate limiting on API endpoints
- Content access control based on subscriptions

### **Performance Optimization**

- Lazy loading for images and videos
- Minified CSS and JavaScript
- Browser caching support
- CDN integration ready
- Video streaming optimization
- Database query optimization

## 🌐 Hosting Requirements

### **Minimum Requirements**

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory**: 128MB (256MB recommended)
- **Storage**: 1GB for theme and basic content

### **Recommended for Video Streaming**

- **PHP**: 8.1 or higher
- **Memory**: 512MB or higher
- **Storage**: 10GB+ for video content
- **CDN**: CloudFlare or similar for video delivery
- **Bandwidth**: Sufficient for video streaming

### **cPanel Hosting Setup**

1. **Enable required PHP extensions:**
   - curl, json, mbstring, openssl, fileinfo

2. **Increase PHP limits:**
   ```php
   upload_max_filesize = 500M
   post_max_size = 500M
   max_execution_time = 300
   memory_limit = 512M
   ```

3. **Setup Node.js app (if using backend):**
   - Enable Node.js in cPanel
   - Upload backend files
   - Install dependencies: `npm install`
   - Start the application

## 🆘 Support & Documentation

### **Common Issues**

**Video not playing:**
- Check video URL and format
- Verify user subscription access
- Test browser compatibility

**Backend connection failed:**
- Verify backend URL in theme settings
- Check API authentication keys
- Ensure backend server is running

**Slow loading:**
- Enable caching plugins
- Optimize images and videos
- Use CDN for video delivery

### **Getting Help**

1. **Documentation**: Check this README and inline code comments
2. **WordPress Forums**: Post in WordPress support forums
3. **GitHub Issues**: Report bugs and feature requests
4. **Professional Support**: Contact theme developers

## 📄 License

This theme is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 🚀 Changelog

### Version 2.0.0
- Complete Netflix-style redesign
- Advanced video player with HLS support
- Backend API integration
- Mobile app support
- Subscription management
- Custom post types and taxonomies
- Performance optimizations
- Security enhancements

---

## 🎉 Ready to Launch Your Streaming Platform!

This theme provides everything needed to create a professional streaming platform. Upload, configure, and start building your Netflix-style website today!

For additional support and updates, visit the theme documentation or contact support.