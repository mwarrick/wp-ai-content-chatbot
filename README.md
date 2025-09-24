# WordPress AI Content Chatbot

A powerful WordPress plugin that creates an intelligent chatbot powered by Claude AI to help visitors find information on your website.

## Features

### 🤖 AI-Powered Chatbot
- **Claude AI Integration**: Uses Anthropic's Claude models for natural language understanding
- **Content-Aware**: Only responds based on your actual website content
- **Anti-Hallucination**: Prevents AI from making up information not in your content
- **Clickable Links**: Automatically includes links to relevant pages in responses

### 📊 Content Indexing
- **Smart Indexing**: Automatically extracts and indexes your website content
- **Keyword Extraction**: Identifies top keywords from each page for better search
- **Post Type Control**: Choose which content types to index (posts, pages, custom types)
- **Exclusion Options**: Exclude specific post types or keywords from indexing

### 🎨 Customizable Interface
- **Flexible Positioning**: Place chatbot in bottom-right, bottom-left, or custom positions
- **Brand Colors**: Customize primary color to match your site
- **Responsive Design**: Works perfectly on desktop and mobile devices
- **Custom Messages**: Set your own welcome message and placeholder text

### ⚙️ Advanced Configuration
- **Model Selection**: Choose from various Claude AI models
- **System Prompts**: Customize how the AI responds to users
- **Content Search**: Built-in search functionality to test content indexing
- **Admin Dashboard**: Comprehensive management interface

### 🔍 Debugging & Analytics
- **Interaction Logging**: Complete database logging of all chatbot interactions
- **Error Tracking**: Detailed error messages and response time monitoring
- **Usage Analytics**: Track user queries, success rates, and performance metrics
- **User Feedback System**: Thumbs up/down feedback collection with database storage
- **Feedback Analytics**: Track helpfulness rates and user satisfaction metrics
- **Keyword Management**: Visual interface for managing excluded keywords with frequency counts

## Installation

1. **Download** the plugin files to your WordPress `/wp-content/plugins/` directory
2. **Activate** the plugin through the 'Plugins' menu in WordPress
3. **Configure** your Claude API key in the AI Chatbot settings
4. **Index** your content using the Content Index page
5. **Customize** appearance and behavior in Settings

## Configuration

### API Setup
1. Get your Claude API key from [Anthropic](https://console.anthropic.com/)
2. Navigate to **AI Chatbot > Settings**
3. Enter your API key in the "Claude API Key" field
4. Test the connection using the "Test Connection" button

### Content Indexing
1. Go to **AI Chatbot > Content Index**
2. Click "Index All Content" to scan your website
3. Review indexed content and adjust post type settings as needed
4. Use "Search Content" to test how the chatbot will find information

### Customization
- **Appearance**: Customize colors, positioning, and window dimensions
- **Behavior**: Set welcome messages, system prompts, and response styles
- **Content**: Choose which post types to include/exclude from indexing

## File Structure

```
wp-ai-content-chatbot/
├── ai-content-chatbot.php          # Main plugin file
├── assets/
│   ├── css/
│   │   └── chatbot.css            # Frontend styles
│   └── js/
│       ├── chatbot.js             # Frontend JavaScript
│       └── admin/
│           ├── admin-dashboard.js  # Dashboard functionality
│           ├── content-index.js    # Content management
│           └── model-selector.js   # Model selection
├── includes/
│   └── admin/
│       └── pages/
│           ├── admin-dashboard.php
│           ├── chat-window.php
│           ├── content-index.php
│           ├── edit-indexed-content.php
│           ├── excluded-keywords.php
│           ├── model-selector.php
│           ├── search-content.php
│           └── settings.php
└── README.md
```

## How It Works

### 1. Content Indexing
- Scans your published posts and pages
- Extracts keywords using intelligent filtering
- Stores content in a custom database table
- Respects your post type inclusion/exclusion settings

### 2. Search Algorithm
- **Phrase Handling**: Recognizes quoted phrases and special terms like "Power BI"
- **Stopword Filtering**: Ignores common words like "the", "and", "does"
- **Multi-Field Search**: Searches across keywords, title, tags, and content
- **Relevance Scoring**: Ranks results by importance (title/keywords > tags > content)

### 3. AI Response Generation
- Sends relevant content to Claude AI with strict instructions
- Prevents hallucination by only using provided content
- Formats responses with clickable links to relevant pages
- Returns helpful messages when insufficient information is available

## Database Schema

The plugin creates two custom tables:

### Content Index Table (`wp_chatbot_content_index`)
```sql
CREATE TABLE wp_chatbot_content_index (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    post_type varchar(20) NOT NULL,
    title text NOT NULL,
    content longtext NOT NULL,
    url varchar(255) NOT NULL,
    location varchar(100) DEFAULT '',
    tags text,
    keywords text,
    indexed_date datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

### Interaction Logs Table (`wp_chatbot_interactions`)
```sql
CREATE TABLE wp_chatbot_interactions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_query text NOT NULL,
    ai_response longtext,
    relevant_content longtext,
    error_message text,
    response_time_ms int(11) DEFAULT 0,
    api_model varchar(100),
    user_ip varchar(45),
    user_agent text,
    timestamp datetime DEFAULT CURRENT_TIMESTAMP,
    success tinyint(1) DEFAULT 1,
    feedback_rating tinyint(1) DEFAULT NULL,
    feedback_helpful tinyint(1) DEFAULT NULL,
    feedback_comment text,
    feedback_timestamp datetime DEFAULT NULL,
    PRIMARY KEY (id)
);
```

## Security Features

- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Capability Checks**: Admin functions require proper user permissions
- **Input Sanitization**: All user inputs are sanitized before processing
- **SQL Injection Protection**: Uses WordPress prepared statements

## Troubleshooting

### Chatbot Not Appearing
- Check if the plugin is activated
- Verify the chatbot is enabled in Settings
- Ensure your theme includes `wp_footer()` hook

### No Responses from AI
- Verify your Claude API key is correct
- Test the connection in Model Selector
- Check if content has been indexed
- Review system prompt configuration

### Poor Search Results
- Re-index your content after making changes
- Adjust post type inclusion/exclusion settings
- Review and update excluded keywords
- Test search functionality in Content Index

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Claude API Key**: Required for AI functionality
- **Internet Connection**: Required for API calls

## Support

**This plugin is provided AS-IS with no support or warranty.**

For issues, feature requests, or questions:
1. Check the troubleshooting section above
2. Review the admin dashboard for configuration issues
3. Test your API connection and content indexing
4. Use the built-in debugging tools (Interaction Logs) to diagnose issues

**No support, updates, or assistance is provided. Use at your own risk.**

## Changelog

### Version 1.8.1 - Feedback System Stable Release
- **✅ Complete Feedback System**: Fully functional thumbs up/down feedback with immediate visual response
- **🔧 Fixed Event Handling**: Resolved click event delegation issues with direct event handlers
- **📊 Enhanced Analytics**: Complete feedback tracking and helpfulness rate calculations
- **🎯 Production Ready**: Stable build with all feedback functionality working end-to-end

### Version 1.8.0 - Major Feature Release
- **🔍 Comprehensive Debugging System**: Added complete interaction logging with database storage
- **📊 Admin Analytics Dashboard**: New "Interaction Logs" page with filtering, stats, and detailed views
- **👍 User Feedback System**: Thumbs up/down feedback collection with immediate visual feedback
- **📈 Feedback Analytics**: Track helpfulness rates, user satisfaction, and response quality metrics
- **🛡️ Enhanced Error Handling**: Improved timeout handling (60s), exception catching, and error logging
- **🚫 Fixed Keyword Exclusion**: Excluded keywords now properly work in both indexing and search
- **🎯 Improved Search Algorithm**: Better phrase handling, stopword filtering, and relevance scoring
- **🔗 Restored Page Linking**: Fixed chatbot responses to include clickable links to relevant content
- **🎨 Enhanced Keyword Management**: Redesigned excluded keywords page with visual interface and frequency counts
- **📝 Anti-Hallucination System**: Stricter system prompts and content validation to prevent AI from making up information
- **⚡ Performance Improvements**: Optimized search queries and response times

### Version 1.7.4
- Fixed page linking functionality in chatbot responses
- Improved content search with phrase handling and relevance scoring
- Enhanced system prompt to prevent AI hallucination
- Added content validation to prevent responses when no relevant content found
- Refactored codebase into modular structure with separate asset files

### Version 1.7.3
- Initial release with core chatbot functionality
- Claude AI integration
- Content indexing and search
- Admin dashboard and configuration options

## License

This plugin is provided as-is for educational and commercial use. Please ensure you comply with Anthropic's API terms of service when using Claude AI features.