<?php
/**
 * Excluded Keywords page template
 */

if (isset($_POST['submit'])) {
    // Sanitize and update the option
    $excluded_keywords = sanitize_textarea_field($_POST['ai_chatbot_excluded_keywords']);
    update_option('ai_chatbot_excluded_keywords', $excluded_keywords);
    echo '<div class="notice notice-success"><p>Excluded keywords saved!</p></div>';
}
$current_excluded_keywords = get_option('ai_chatbot_excluded_keywords', '');
?>
<div class="wrap">
    <h1>Excluded Keywords</h1>
    <p>Enter a comma-separated list of words to be excluded from the keyword extraction process.</p>
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row">Excluded Keywords</th>
                <td>
                    <textarea name="ai_chatbot_excluded_keywords" rows="5" cols="50" class="large-text code"><?php echo esc_textarea($current_excluded_keywords); ?></textarea>
                    <p class="description">Separate words with a comma (e.g., `the, and, or, pdf-embedder`).</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
