<?php
/**
 * Model Selector page template
 */

// Handle model saving via GET parameter
if (isset($_GET['save_model']) && !empty($_GET['save_model'])) {
    $model = sanitize_text_field($_GET['save_model']);
    update_option('ai_chatbot_model', $model);
    echo '<div class="notice notice-success is-dismissible"><p><strong>Success!</strong> Model saved: ' . esc_html($model) . '</p></div>';
}
?>
<div class="wrap">
    <h1>🔧 Claude Model Selector</h1>
    
    <div style="background: white; padding: 30px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
        <h2>Current Configuration</h2>
        <table class="form-table">
            <tr>
                <th>Plugin Version:</th>
                <td><?php echo AI_CHATBOT_VERSION; ?></td>
            </tr>
            <tr>
                <th>Current Model:</th>
                <td><strong><?php echo esc_html(get_option('ai_chatbot_model', 'Not Set')); ?></strong></td>
            </tr>
            <tr>
                <th>API Key Status:</th>
                <td><?php echo !empty(get_option('ai_chatbot_api_key', '')) ? '<span style="color: green;">✅ Configured</span>' : '<span style="color: red;">❌ Not Set'; ?></span></td>
            </tr>
        </table>
        
        <?php if (empty(get_option('ai_chatbot_api_key', ''))): ?>
        <div class="notice notice-warning">
            <p><strong>Warning:</strong> Please set your Claude API key in <a href="<?php echo admin_url('admin.php?page=ai-chatbot-settings'); ?>">Settings</a> before testing models.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <div style="background: white; padding: 30px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
        <h2>Step 1: Get Available Models</h2>
        <p>Click this button to query Claude API for all currently available models:</p>
        <button type="button" id="get-models" class="button button-primary button-large" style="background: #28a745; border-color: #28a745;">
            📋 Get Available Claude Models
        </button>
        
        <h2 style="margin-top: 30px;">Step 2: Test API Connection</h2>
        <p>Test your current model configuration:</p>
        <button type="button" id="test-api" class="button button-large">
            🌐 Test Current Model
        </button>
        
        <div id="results" style="margin-top: 30px; padding: 20px; background: #f1f1f1; border: 1px solid #ddd; border-radius: 4px; min-height: 100px;">
            <h3>Results</h3>
            <p id="results-content"><em>Click a button above to see results...</em></p>
        </div>
    </div>
</div>
