<?php
/**
 * Edit Indexed Content page template
 */

global $wpdb;
$table_name = $wpdb->prefix . 'chatbot_content_index';
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$indexed_content = null;

if ($post_id) {
    $indexed_content = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id));
}

if (!$indexed_content) {
    echo '<div class="wrap"><h1>Error</h1><p>No content found for this ID.</p></div>';
    return;
}

if (isset($_POST['submit_keywords'])) {
    $new_keywords = sanitize_textarea_field($_POST['new_keywords']);
    $wpdb->update(
        $table_name,
        ['keywords' => $new_keywords],
        ['post_id' => $post_id],
        ['%s'],
        ['%d']
    );
    echo '<div class="notice notice-success"><p>Keywords updated successfully!</p></div>';
    $indexed_content->keywords = $new_keywords; // Update the displayed keywords
}
?>
<div class="wrap">
    <h1>Edit Keywords</h1>
    <p>Editing keywords for: <strong><?php echo esc_html($indexed_content->title); ?></strong></p>
    <p>URL: <a href="<?php echo esc_url($indexed_content->url); ?>" target="_blank"><?php echo esc_url($indexed_content->url); ?></a></p>
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row">Keywords</th>
                <td>
                    <textarea name="new_keywords" rows="5" cols="50" class="large-text code"><?php echo esc_textarea($indexed_content->keywords); ?></textarea>
                    <p class="description">Enter a comma-separated list of keywords. These are used to find relevant content for the chatbot.</p>
                </td>
            </tr>
        </table>
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
        <?php submit_button('Save Keywords', 'primary', 'submit_keywords'); ?>
    </form>
    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-chatbot-content')); ?>" class="button">← Back to Content Index</a>
</div>
