<?php
/**
 * Content Index page template
 */

global $wpdb;
$table_name = $wpdb->prefix . 'chatbot_content_index';
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;
$total_items = $this->get_indexed_content_count();
$total_pages = ceil($total_items / $per_page);
?>
<div class="wrap">
    <h1>Content Index</h1>
    <p>Manage your website content index for the chatbot to provide relevant answers.</p>
    
    <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
        <h2>Index Management</h2>
        <p>The chatbot uses indexed content from your website to provide relevant answers to visitor questions.</p>
        
        <div style="margin: 20px 0;">
            <button type="button" id="index-all-content" class="button button-primary button-large">
                📚 Index All Content
            </button>
            <button type="button" id="clear-index" class="button button-large" style="margin-left: 10px;">
                🗑️ Clear Index
            </button>
        </div>
        
        <div id="indexing-progress" style="display: none; margin: 20px 0; padding: 15px; background: #f0f6fc; border: 1px solid #c9e3f7; border-radius: 4px;">
            <p><strong>Indexing in progress...</strong> <span id="progress-text">0%</span></p>
            <div style="background: #e5e7eb; height: 20px; border-radius: 10px; overflow: hidden;">
                <div id="progress-bar" style="background: #3b82f6; height: 100%; width: 0%; transition: width 0.3s ease;"></div>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>Current Status</h3>
            <p><strong>Indexed Items:</strong> <?php echo $total_items; ?></p>
            <p><strong>Last Updated:</strong> <?php echo $this->get_last_index_date(); ?></p>
        </div>
    </div>
    
    <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
        <h2>Recent Indexed Content</h2>
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
                <?php $this->display_indexed_content($per_page, $offset); ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1) : ?>
            <div class="pagination">
                <?php
                $base_url = admin_url('admin.php?page=ai-chatbot-content');
                if ($current_page > 1) {
                    echo '<a href="' . esc_url($base_url . '&paged=' . ($current_page - 1)) . '" class="button">« Previous</a>';
                }
                echo '<span> Page ' . $current_page . ' of ' . $total_pages . ' </span>';
                if ($current_page < $total_pages) {
                    echo '<a href="' . esc_url($base_url . '&paged=' . ($current_page + 1)) . '" class="button">Next »</a>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
