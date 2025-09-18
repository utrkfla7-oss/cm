# Netflix Mobile App

Flutter-based mobile application for the Netflix Streaming Platform.

## Features

- **Native Video Player** with HLS support
- **User Authentication** and profiles
- **Content Browsing** with search and filters
- **Offline Downloads** for premium users
- **Push Notifications** for new content
- **Multi-language Support**
- **Adaptive UI** for phones and tablets

## Setup

### Prerequisites
- Flutter 3.0+
- Android Studio / Xcode
- Firebase project (for notifications)

### Installation

1. **Install Flutter**
   ```bash
   # Follow official Flutter installation guide
   flutter doctor
   ```

2. **Get Dependencies**
   ```bash
   flutter pub get
   ```

3. **Configure Firebase**
   ```bash
   # Add google-services.json (Android)
   # Add GoogleService-Info.plist (iOS)
   ```

4. **Configure API**
   ```dart
   // lib/utils/constants.dart
   static const String baseUrl = 'https://your-domain.com:3001/api';
   ```

### Development

```bash
# Run in debug mode
flutter run

# Run on specific device
flutter devices
flutter run -d device_id

# Build for release
flutter build apk --release  # Android
flutter build ios --release  # iOS
```

### Testing

```bash
# Run tests
flutter test

# Run integration tests
flutter drive --target=test_driver/app.dart
```

## Project Structure

```
lib/
├── main.dart              # App entry point
├── models/               # Data models
├── providers/            # State management
├── screens/              # UI screens
├── services/             # API and external services
├── utils/                # Utilities and constants
└── widgets/              # Reusable UI components
```

## Configuration

### API Configuration
Update `lib/utils/constants.dart` with your backend URL:

```dart
class ApiConstants {
  static const String baseUrl = 'https://your-domain.com:3001/api';
  static const String mediaUrl = 'https://your-domain.com/media';
}
```

### Firebase Setup
1. Create Firebase project
2. Add Android/iOS apps
3. Download configuration files
4. Enable FCM for notifications

## Build & Deploy

### Android
```bash
# Debug APK
flutter build apk --debug

# Release APK
flutter build apk --release

# App Bundle for Play Store
flutter build appbundle --release
```

### iOS
```bash
# Build for simulator
flutter build ios --simulator

# Build for device
flutter build ios --release
```

## Features Implementation

### Video Player
- HLS streaming support
- Multiple quality options
- Subtitle support
- Chromecast integration
- Picture-in-picture mode

### Authentication
- JWT token management
- Biometric authentication
- Session management
- Auto-login

### Content Management
- Browse movies and shows
- Search functionality
- Favorites management
- Watch history
- Continue watching

### Offline Support
- Download videos for offline viewing
- Manage downloaded content
- Storage optimization

## Platform-specific Notes

### Android
- Minimum SDK: 21 (Android 5.0)
- Target SDK: 34 (Android 14)
- Required permissions: Internet, Storage, Camera, Microphone

### iOS
- Minimum version: iOS 12.0
- Required capabilities: Network, Media playback
- App Store compliance ready

## Contributing

1. Fork the repository
2. Create feature branch
3. Follow Flutter style guide
4. Add tests for new features
5. Create pull request

## Support

For mobile app specific issues:
- Check Flutter doctor: `flutter doctor`
- Review device logs
- Test on multiple devices/OS versions