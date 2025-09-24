<?php
/**
 * Chat Window settings page template
 */

if (isset($_POST['submit'])) {
    update_option('ai_chatbot_window_title', sanitize_text_field($_POST['ai_chatbot_window_title']));
    update_option('ai_chatbot_window_width', intval($_POST['ai_chatbot_window_width']));
    update_option('ai_chatbot_window_height', intval($_POST['ai_chatbot_window_height']));
    update_option('ai_chatbot_title_height', intval($_POST['ai_chatbot_title_height']));
    update_option('ai_chatbot_reply_height', intval($_POST['ai_chatbot_reply_height']));
    update_option('ai_chatbot_input_spacing', intval($_POST['ai_chatbot_input_spacing']));
    echo '<div class="notice notice-success"><p>Chat window settings saved!</p></div>';
}

$current_title = get_option('ai_chatbot_window_title');
$current_width = get_option('ai_chatbot_window_width');
$current_height = get_option('ai_chatbot_window_height');
$current_title_height = get_option('ai_chatbot_title_height');
$current_reply_height = get_option('ai_chatbot_reply_height');
$current_input_spacing = get_option('ai_chatbot_input_spacing');
?>
<div class="wrap">
    <h1>Chat Window Settings</h1>
    <p>Customize the appearance of the front-end chat window.</p>
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ai_chatbot_window_title">Chat Window Title</label></th>
                <td>
                    <input type="text" id="ai_chatbot_window_title" name="ai_chatbot_window_title" value="<?php echo esc_attr($current_title); ?>" class="regular-text" />
                    <p class="description">The title that appears at the top of the chat window.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ai_chatbot_window_width">Window Width (px)</label></th>
                <td>
                    <input type="number" id="ai_chatbot_window_width" name="ai_chatbot_window_width" value="<?php echo esc_attr($current_width); ?>" class="regular-text" min="250" max="600" />
                    <p class="description">The width of the chat window in pixels. (e.g., 350)</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ai_chatbot_window_height">Window Height (px)</label></th>
                <td>
                    <input type="number" id="ai_chatbot_window_height" name="ai_chatbot_window_height" value="<?php echo esc_attr($current_height); ?>" class="regular-text" min="300" max="800" />
                    <p class="description">The height of the chat window in pixels. (e.g., 450)</p>
                </td>
            </tr>
             <tr>
                <th scope="row"><label for="ai_chatbot_title_height">Title Bar Height (px)</label></th>
                <td>
                    <input type="number" id="ai_chatbot_title_height" name="ai_chatbot_title_height" value="<?php echo esc_attr($current_title_height); ?>" class="regular-text" min="40" max="100" />
                    <p class="description">The height of the header area with the title and close button.</p>
                </td>
            </tr>
             <tr>
                <th scope="row"><label for="ai_chatbot_reply_height">Reply Area Height (px)</label></th>
                <td>
                    <input type="number" id="ai_chatbot_reply_height" name="ai_chatbot_reply_height" value="<?php echo esc_attr($current_reply_height); ?>" class="regular-text" min="150" max="600" />
                    <p class="description">The height of the message display area. Adjust this to balance with other elements.</p>
                </td>
            </tr>
             <tr>
                <th scope="row"><label for="ai_chatbot_input_spacing">Input Area Spacing (px)</label></th>
                <td>
                    <input type="number" id="ai_chatbot_input_spacing" name="ai_chatbot_input_spacing" value="<?php echo esc_attr($current_input_spacing); ?>" class="regular-text" min="0" max="50" />
                    <p class="description">The space below the message input box. This affects the overall height calculation.</p>
                </td>
            </tr>
        </table>
        <?php submit_button('Save Settings'); ?>
    </form>
</div>
