<?php
/**
 * Settings page template
 */

if (isset($_POST['submit'])) {
    update_option('ai_chatbot_enabled', isset($_POST['ai_chatbot_enabled']) ? '1' : '0');
    update_option('ai_chatbot_api_key', sanitize_text_field($_POST['ai_chatbot_api_key']));
    update_option('ai_chatbot_model', sanitize_text_field($_POST['ai_chatbot_model']));
    update_option('ai_chatbot_welcome_message', sanitize_textarea_field($_POST['ai_chatbot_welcome_message']));
    update_option('ai_chatbot_primary_color', sanitize_hex_color($_POST['ai_chatbot_primary_color']));
    update_option('ai_chatbot_system_prompt', sanitize_textarea_field($_POST['ai_chatbot_system_prompt']));
    
    echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
}
?>
<div class="wrap">
    <h1>Chatbot Settings</h1>
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row">Enable Chatbot</th>
                <td>
                    <input type="checkbox" id="ai_chatbot_enabled" name="ai_chatbot_enabled" value="1" <?php checked(get_option('ai_chatbot_enabled', '1'), '1'); ?> />
                    <label for="ai_chatbot_enabled">Show chatbot on frontend</label>
                </td>
            </tr>
            <tr>
                <th scope="row">Claude API Key</th>
                <td>
                    <input type="password" name="ai_chatbot_api_key" value="<?php echo esc_attr(get_option('ai_chatbot_api_key', '')); ?>" class="regular-text" />
                    <p class="description">Get your API key from <a href="https://console.anthropic.com" target="_blank">Anthropic Console</a></p>
                </td>
            </tr>
            <tr>
                <th scope="row">Claude Model</th>
                <td>
                    <input type="text" name="ai_chatbot_model" value="<?php echo esc_attr(get_option('ai_chatbot_model', 'claude-3-5-sonnet-20241022')); ?>" class="regular-text" />
                    <p class="description">Use the <a href="<?php echo admin_url('admin.php?page=ai-chatbot-models'); ?>">Model Selector</a> to find current models</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Welcome Message</th>
                <td>
                    <textarea name="ai_chatbot_welcome_message" rows="3" cols="50"><?php echo esc_textarea(get_option('ai_chatbot_welcome_message', 'Hi! How can I help you today?')); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">Primary Color</th>
                <td>
                    <input type="color" name="ai_chatbot_primary_color" value="<?php echo esc_attr(get_option('ai_chatbot_primary_color', '#007cba')); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">System Prompt</th>
                <td>
                    <textarea name="ai_chatbot_system_prompt" rows="8" cols="50" class="large-text code"><?php echo esc_textarea(get_option('ai_chatbot_system_prompt')); ?></textarea>
                    <p class="description">This is the core instruction set for the AI. Use <code>[SITE_NAME]</code> and <code>[RELEVANT_CONTENT]</code> as dynamic placeholders.</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
