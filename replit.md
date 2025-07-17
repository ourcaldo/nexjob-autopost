# Nexjob Autopost WordPress Plugin

## Overview

This is a custom WordPress plugin that automatically sends POST requests to an external API whenever a new post is created in the custom post type "lowongan-kerja" (job listings). The plugin integrates with the NexPocket autopost service to automatically publish job postings to external platforms.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

The plugin follows a standard WordPress plugin architecture with:

- **Backend Integration**: WordPress hooks and actions for post lifecycle management
- **API Integration**: HTTP client for external API communication with NexPocket service
- **Admin Interface**: WordPress admin dashboard integration for configuration and monitoring
- **Logging System**: Built-in logging for API requests and responses

## Key Components

### 1. Post Hook System
- Monitors new posts in the "lowongan-kerja" custom post type
- Triggers API requests automatically when new job postings are published
- Uses WordPress `save_post` or `publish_post` hooks

### 2. API Client
- **Endpoint**: `https://autopost.nexpocket.com/api/public/v1/post`
- **Authentication**: Bearer token via Authorization header
- **Content Transformation**: Converts WordPress post data to NexPocket API format

### 3. Admin Dashboard
- Configuration interface for API settings (URL, authorization token)
- Test API connection functionality
- Statistics and monitoring dashboard
- Request/response logging viewer

### 4. Data Transformation Layer
- Maps WordPress post fields to API request format
- Handles tag extraction from "tag-loker" taxonomy
- Generates dynamic content with city location and post links
- Creates hashtags from post tags

## Data Flow

1. **Post Creation**: User creates/publishes a new "lowongan-kerja" post
2. **Hook Trigger**: WordPress fires post publication hook
3. **Data Extraction**: Plugin extracts relevant post data (title, location, tags, URL)
4. **Content Generation**: Creates formatted content string with location and hashtags
5. **API Request**: Sends POST request to NexPocket API with transformed data
6. **Response Handling**: Logs success/failure and displays admin notifications
7. **Statistics Update**: Updates dashboard metrics and logs

## External Dependencies

### NexPocket API
- **Purpose**: External autopost service for social media publishing
- **Authentication**: Static bearer token
- **Rate Limits**: Unknown (should be monitored)
- **Data Format**: JSON with specific schema for posts, tags, and scheduling

### WordPress Dependencies
- **Custom Post Type**: "lowongan-kerja" must be registered
- **Custom Taxonomy**: "tag-loker" for job tags
- **Custom Fields**: "nexjob_lokasi_kota" for city location data

## Deployment Strategy

### Installation
- Standard WordPress plugin installation via upload or plugins directory
- Automatic database table creation for logging (if implemented)
- Default configuration setup

### Configuration
- Admin must configure API URL and authorization token
- Test API connection before activation
- Optional: Configure posting schedule and content templates

### Monitoring
- Built-in logging system for tracking API requests
- Admin dashboard for monitoring success/failure rates
- Error notifications for failed API requests

### Error Handling
- Graceful failure when API is unavailable
- Retry mechanism for failed requests (recommended)
- Admin notifications for persistent failures
- Fallback logging when external logging services are unavailable

## Technical Implementation Notes

### Content Template
The plugin generates content using this template:
```
Lowongan kerja di kota {nexjob_lokasi_kota}, cek selengkapnya di {post_link}.

{hashtags}
```

### API Request Structure
- **Type**: "now" (immediate posting)
- **ShortLink**: true
- **Date**: Current timestamp in ISO format
- **Tags**: Extracted from "tag-loker" taxonomy
- **Content**: Generated from template with dynamic values
- **Images**: Excluded as per requirements

### Security Considerations
- API token stored securely in WordPress options
- Nonce verification for admin AJAX requests
- Input sanitization for all user inputs
- Rate limiting to prevent API abuse

## Recent Enhancements (2025-07-17)

### Major UI Restructure (Evening)
- ✓ **Separated Admin Interface**: Split the messy single-page admin into clean, focused sections
  - **Main Dashboard**: Statistics overview, recent configurations, recent errors, logs, and bulk actions
  - **Settings Page**: General plugin settings (API endpoint, auth token, log retention, retry settings, email notifications)
  - **Configurations Page**: Dedicated page for managing autopost configurations with CRUD operations
- ✓ **Removed Integration ID from General Settings**: Moved integration ID to individual autopost configurations for better flexibility
- ✓ **Fixed Log Details Modal**: Implemented proper AJAX-based log details viewer with formatted JSON display
- ✓ **Enhanced Admin Menu Structure**: Clear separation between Dashboard, Configurations, and Settings
- ✓ **Improved User Experience**: 
  - Clean, intuitive navigation with proper WordPress admin styling
  - Responsive design for mobile compatibility
  - Better visual feedback for actions (loading states, success messages)
  - Simplified configuration management with drag-and-drop style interface

### Technical Improvements (Evening)
- **Page Templates**: 
  - `admin/main-page.php`: Dashboard with statistics and logs
  - `admin/settings-page.php`: General plugin settings
  - `admin/configs-page.php`: Configuration management
- **Enhanced JavaScript**: 
  - Improved AJAX handlers for log details and configuration management
  - Better error handling and user feedback
  - Responsive modal system for log viewing
- **Updated CSS**: 
  - Modern, clean styling with proper spacing and typography
  - Responsive grid layouts for statistics and configurations
  - Better status badges and visual indicators
  - Mobile-friendly design patterns

### Configuration Management Enhancements
- **Individual Integration IDs**: Each autopost configuration now has its own integration ID
- **Simplified Form Interface**: Streamlined configuration creation and editing
- **Better Visual Feedback**: Loading states, success messages, and error handling
- **Improved Navigation**: Clear breadcrumbs and action buttons

### Advanced Features Added (Morning)
- ✓ **Retry Mechanism**: Automatic retry for failed API requests with exponential backoff (2, 4, 8 minutes)
- ✓ **Bulk Resend**: Admin interface to resend multiple posts at once with selection dropdown and manual ID input
- ✓ **Email Notifications**: Configurable email alerts for final failures and retry successes
- ✓ **Dashboard Widget**: WordPress dashboard widget showing activity statistics and recent errors
- ✓ **Enhanced Admin Interface**: New settings for max retries, email notifications, and notification email
- ✓ **Improved Logging**: Added retry count tracking and request type classification (auto/bulk_resend)

### Multi-Configuration System (Afternoon)
- ✓ **Multiple Autopost Configurations**: Support for unlimited autopost configurations with different integration IDs
- ✓ **Dynamic Post Type Support**: Configure autoposts for any post type (posts, pages, custom post types)
- ✓ **Advanced Placeholder System**: Dynamic placeholders for custom fields, taxonomies, and post data
- ✓ **Flexible Content Templates**: Customizable content templates with placeholder replacement
- ✓ **Smart Taxonomy Handling**: Support for hashtags ({{hashtags:taxonomy}}) and term lists ({{terms:taxonomy}})
- ✓ **CRUD Configuration Management**: Add, edit, delete configurations through admin interface
- ✓ **Interactive Placeholder Builder**: Click-to-insert placeholder system in admin
- ✓ **Multi-Configuration Processing**: Single post can trigger multiple autopost configurations

### Technical Improvements
- **Database Schema**: 
  - Enhanced logs table with `autopost_config_id` column for tracking which config was used
  - New `nexjob_autopost_configs` table for storing multiple configurations
  - Backward compatibility maintained with existing single-configuration setups
- **Error Handling**: Comprehensive retry logic with scheduled WordPress cron events
- **User Experience**: 
  - New WordPress admin menu structure with dedicated sections
  - Individual retry buttons on failed log entries
  - Real-time placeholder preview based on selected post type
- **Monitoring**: Enhanced statistics display with success rates and activity metrics

### Configuration Options
- **Max Retry Attempts**: 0-10 configurable retry attempts for failed requests
- **Email Notifications**: Toggle for email alerts on failures and retry successes
- **Notification Email**: Customizable email address (defaults to admin email)
- **Bulk Operations**: Select posts from dropdown or enter comma-separated post IDs
- **Multiple Integrations**: Each configuration can have different integration IDs and content templates
- **Post Type Flexibility**: Support for all WordPress post types with dynamic field detection