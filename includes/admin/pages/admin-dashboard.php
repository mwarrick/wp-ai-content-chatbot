<?php
/**
 * Admin Dashboard page template
 */
?>
<div class="wrap">
    <h1>AI Content Chatbot</h1>
    
    <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; margin: 20px 0; border-radius: 4px;">
        <h2>✅ Plugin Status</h2>
        <table class="form-table">
            <tr>
                <th>Version:</th>
                <td><?php echo AI_CHATBOT_VERSION; ?></td>
            </tr>
            <tr>
                <th>PHP Version:</th>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <th>Current Model:</th>
                <td><strong><?php echo esc_html(get_option('ai_chatbot_model', 'Not Set')); ?></strong></td>
            </tr>
            <tr>
                <th>API Key:</th>
                <td><?php echo !empty(get_option('ai_chatbot_api_key', '')) ? '✅ Configured' : '❌ Not Set'; ?></span></td>
            </tr>
        </table>
        
        <p><a href="<?php echo admin_url('admin.php?page=ai-chatbot-models'); ?>" class="button button-primary button-large">🔧 Open Model Selector</a></p>
    </div>
    
    <div style="background: white; border: 2px solid #007cba; padding: 30px; margin: 20px 0; border-radius: 8px;">
        <h2>🧪 Quick Tests</h2>
        
        <div style="margin: 20px 0;">
            <h3>Test WordPress AJAX</h3>
            <button type="button" id="test-wp-ajax" class="button button-large" style="background: #28a745; color: white; padding: 15px 30px; font-size: 16px;">
                🔍 Test WordPress AJAX
            </button>
        </div>
        
        <div style="margin: 20px 0;">
            <h3>Test Claude API</h3>
            <button type="button" id="test-claude-api" class="button button-large button-primary" style="padding: 15px 30px; font-size: 16px;">
                🌐 Test Claude API
            </button>
        </div>
        
        <div id="test-results" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; min-height: 100px;">
            <h3>📋 Test Results</h3>
            <p id="result-text">Click a test button above to see results...</p>
        </div>
    </div>
</div>
