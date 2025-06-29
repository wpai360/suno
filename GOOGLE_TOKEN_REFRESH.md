# Google Token Refresh System

This document explains the Google token refresh system implemented in this Laravel application.

## Overview

Google OAuth2 access tokens expire after 60 minutes. This system automatically refreshes tokens 5 minutes before they expire to ensure continuous API access for Google Drive and YouTube services.

## Components

### 1. GoogleTokenService (`app/Services/GoogleTokenService.php`)
- Central service for managing Google access tokens
- Handles token storage, loading, and refresh logic
- Uses both file storage and cache for performance
- Automatically refreshes tokens when they're within 5 minutes of expiry

### 2. RefreshGoogleTokenJob (`app/Jobs/RefreshGoogleTokenJob.php`)
- Background job for token refresh operations
- Can be scheduled or dispatched manually
- Includes error handling and logging

### 3. GoogleAuthController (`app/Http/Controllers/GoogleAuthController.php`)
- Handles Google OAuth flow
- Provides endpoints for token management
- Includes token status checking and manual refresh

### 4. ScheduleServiceProvider (`app/Providers/ScheduleServiceProvider.php`)
- Schedules automatic token refresh every 50 minutes
- Ensures tokens are refreshed before expiry

## Routes

### Authentication
- `GET /auth/google` - Initiate Google OAuth
- `GET /auth/google/callback` - Handle OAuth callback

### Token Management
- `GET /auth/google/token-status` - Check token status
- `POST /auth/google/refresh-token` - Manually refresh token
- `DELETE /auth/google/clear-token` - Clear stored token

### Testing
- `GET /test/google-refresh` - Manually dispatch refresh job

## How It Works

### 1. Token Storage
- Tokens are stored in `storage/app/public/google_access_token.json`
- Also cached in Laravel cache for faster access
- Cache expires after 55 minutes

### 2. Automatic Refresh
- Scheduled job runs every 50 minutes
- Checks if token expires within 5 minutes
- Automatically refreshes if needed
- Logs all operations

### 3. Manual Refresh
- Services automatically refresh tokens when needed
- Dashboard provides manual refresh button
- API endpoints for programmatic access

### 4. Error Handling
- Graceful handling of expired refresh tokens
- Comprehensive logging
- Fallback mechanisms

## Configuration

### Environment Variables
```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://your-domain/auth/google/callback
```

### Google API Scopes
The application requests these scopes:
- `https://www.googleapis.com/auth/drive.file`
- `https://www.googleapis.com/auth/drive`
- `https://www.googleapis.com/auth/youtube.upload`
- `https://www.googleapis.com/auth/youtube.readonly`

## Usage

### 1. Initial Setup
1. Configure Google OAuth credentials in `.env`
2. Visit `/auth/google` to authenticate
3. Grant required permissions

### 2. Monitor Token Status
- Visit `/dashboard` to see token status
- Check logs in `storage/logs/google-token-refresh.log`

### 3. Manual Operations
- Use dashboard buttons for manual refresh/clear
- Use API endpoints for programmatic access

## Monitoring

### Logs
- Token refresh operations are logged
- Errors are logged with details
- Check `storage/logs/laravel.log` for general logs
- Check `storage/logs/google-token-refresh.log` for scheduled job logs

### Dashboard
- Real-time token status display
- Visual indicators for token health
- Manual control buttons

## Troubleshooting

### Common Issues

1. **Token Expired**
   - Check if refresh token is available
   - Re-authenticate if refresh token is missing

2. **Scheduled Job Not Running**
   - Ensure Laravel scheduler is running: `php artisan schedule:work`
   - Check cron job setup

3. **Permission Errors**
   - Verify Google API scopes are correct
   - Check Google Cloud Console settings

### Commands
```bash
# Manual token refresh
php artisan google:refresh-token

# Check token status
curl http://your-domain/auth/google/token-status

# Manual job dispatch
curl http://your-domain/test/google-refresh
```

## Security Considerations

1. **Token Storage**
   - Tokens are stored in `storage/app/public/`
   - Ensure proper file permissions
   - Consider encrypting sensitive data

2. **Access Control**
   - Token management routes should be protected
   - Implement proper authentication/authorization

3. **Logging**
   - Sensitive data is not logged
   - Logs contain only operational information

## Future Improvements

1. **Database Storage**
   - Move tokens to database for better security
   - Implement token encryption

2. **Multiple Accounts**
   - Support for multiple Google accounts
   - User-specific token management

3. **Advanced Monitoring**
   - Webhook notifications for token issues
   - Integration with monitoring services 