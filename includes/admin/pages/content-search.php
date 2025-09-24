<?php
/**
 * Content Search page template
 */

global $wpdb;
$table_name = $wpdb->prefix . 'chatbot_content_index';
$search_query = isset($_GET['s']) ? sanitize_text_field(stripslashes($_GET['s'])) : '';
$results = array();

if (!empty($search_query)) {
    $safe_query = '%' . $wpdb->esc_like($search_query) . '%';
    $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE title LIKE %s OR content LIKE %s OR location LIKE %s OR tags LIKE %s OR keywords LIKE %s", $safe_query, $safe_query, $safe_query, $safe_query, $safe_query);
    $results = $wpdb->get_results($sql);
}
?>
<div class="wrap">
    <h1>Content Search</h1>
    <p>Search all indexed pages for specific terms.</p>
    <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="ai-chatbot-content-search" />
            <p class="search-box">
                <label class="screen-reader-text" for="search-input">Search Indexed Content:</label>
                <input type="search" id="search-input" name="s" value="<?php echo esc_attr($search_query); ?>" />
                <input type="submit" id="search-submit" class="button" value="Search" />
            </p>
        </form>
    </div>
    
    <?php if (!empty($search_query) && empty($results)) : ?>
        <div class="notice notice-warning">
            <p>No results found for "<?php echo esc_html($search_query); ?>".</p>
        </div>
    <?php elseif (!empty($results)) : ?>
        <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
            <h2>Search Results (<?php echo count($results); ?>)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 20%;">Title</th>
                        <th style="width: 8%;">Type</th>
                        <th style="width: 10%;">Location</th>
                        <th style="width: 15%;">Tags</th>
                        <th style="width: 25%;">Keywords</th>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 7%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : ?>
                        <tr>
                            <td><?php echo esc_html($result->title); ?></td>
                            <td><?php echo esc_html(ucfirst($result->post_type)); ?></td>
                            <td><?php echo esc_html($result->location); ?></td>
                            <td><?php echo esc_html($result->tags); ?></td>
                            <td><?php echo esc_html($result->keywords); ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($result->indexed_date))); ?></td>
                            <td><a href="<?php echo esc_url(admin_url('admin.php?page=ai-chatbot-edit-indexed-content&post_id=' . $result->post_id)); ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
