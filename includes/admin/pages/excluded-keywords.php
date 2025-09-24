<?php
/**
 * Excluded Keywords page template
 */

// Handle form submission
if (isset($_POST['submit'])) {
    $excluded_keywords = array();
    
    // Get keywords from checkboxes
    if (isset($_POST['excluded_keyword_checkboxes']) && is_array($_POST['excluded_keyword_checkboxes'])) {
        $excluded_keywords = array_map('sanitize_text_field', $_POST['excluded_keyword_checkboxes']);
    }
    
    // Get additional keywords from text input
    if (!empty($_POST['additional_excluded_keywords'])) {
        $additional_keywords = sanitize_text_field($_POST['additional_excluded_keywords']);
        $additional_array = array_map('trim', explode(',', $additional_keywords));
        $additional_array = array_filter($additional_array); // Remove empty values
        $excluded_keywords = array_merge($excluded_keywords, $additional_array);
    }
    
    // Remove duplicates and update option
    $excluded_keywords = array_unique($excluded_keywords);
    $excluded_keywords_string = implode(', ', $excluded_keywords);
    update_option('ai_chatbot_excluded_keywords', $excluded_keywords_string);
    
    echo '<div class="notice notice-success"><p>Excluded keywords saved!</p></div>';
}

// Get current excluded keywords
$current_excluded_keywords = get_option('ai_chatbot_excluded_keywords', '');
$excluded_array = !empty($current_excluded_keywords) ? array_map('trim', explode(',', $current_excluded_keywords)) : array();

// Get all keywords from indexed content
$all_keywords = $this->get_all_keywords();
?>

<div class="wrap">
    <h1>Excluded Keywords</h1>
    <p>Manage keywords that should be excluded from the chatbot's search and keyword extraction process.</p>
    
    <?php if (empty($all_keywords)): ?>
        <div class="notice notice-warning">
            <p><strong>No keywords found!</strong> You need to index your content first. Go to <a href="<?php echo admin_url('admin.php?page=ai-chatbot-content'); ?>">Content Index</a> to index your website content.</p>
        </div>
    <?php else: ?>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Website Keywords</th>
                    <td>
                        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                            <p><strong>Select keywords to exclude from search:</strong></p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 5px; margin-top: 10px;">
                                <?php foreach ($all_keywords as $keyword => $count): ?>
                                    <label style="display: flex; align-items: center; padding: 5px; background: white; border: 1px solid #e0e0e0; border-radius: 3px;">
                                        <input type="checkbox" 
                                               name="excluded_keyword_checkboxes[]" 
                                               value="<?php echo esc_attr($keyword); ?>"
                                               <?php checked(in_array($keyword, $excluded_array)); ?>
                                               style="margin-right: 8px;">
                                        <span style="flex: 1; font-size: 13px;">
                                            <?php echo esc_html($keyword); ?>
                                            <span style="color: #666; font-size: 11px;">(<?php echo $count; ?>)</span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <p class="description">
                            Keywords are shown with their frequency count in parentheses. 
                            Check the box next to any keyword you want to exclude from search results.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Additional Keywords</th>
                    <td>
                        <textarea name="additional_excluded_keywords" 
                                  rows="3" 
                                  cols="50" 
                                  class="large-text code" 
                                  placeholder="Enter additional keywords to exclude, separated by commas (e.g., spam, test, draft)"><?php 
                            // Show only manually added keywords (not from checkboxes)
                            $manual_keywords = array_diff($excluded_array, array_keys($all_keywords));
                            echo esc_textarea(implode(', ', $manual_keywords)); 
                        ?></textarea>
                        <p class="description">
                            Add any additional keywords you want to exclude that aren't in the list above. 
                            Separate multiple keywords with commas.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Current Excluded Keywords</th>
                    <td>
                        <div style="background: #f0f0f0; padding: 10px; border-radius: 3px; max-height: 100px; overflow-y: auto;">
                            <?php if (!empty($excluded_array)): ?>
                                <?php foreach ($excluded_array as $keyword): ?>
                                    <span style="display: inline-block; background: #0073aa; color: white; padding: 2px 6px; margin: 2px; border-radius: 3px; font-size: 12px;">
                                        <?php echo esc_html($keyword); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <em style="color: #666;">No keywords are currently excluded.</em>
                            <?php endif; ?>
                        </div>
                        <p class="description">Preview of all currently excluded keywords.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Excluded Keywords'); ?>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa;">
            <h3>💡 Tips for Keyword Exclusion</h3>
            <ul>
                <li><strong>Common words:</strong> Exclude very common words that don't add search value (e.g., "website", "page", "content")</li>
                <li><strong>Technical terms:</strong> Exclude plugin-specific terms that users wouldn't search for (e.g., "wp-content", "shortcode")</li>
                <li><strong>Brand terms:</strong> Consider excluding your own brand name if it appears too frequently</li>
                <li><strong>Test searches:</strong> Use the <a href="<?php echo admin_url('admin.php?page=ai-chatbot-search'); ?>">Search Content</a> page to test how exclusions affect results</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<style>
.form-table th {
    width: 200px;
    vertical-align: top;
    padding-top: 20px;
}

.form-table td {
    padding-top: 15px;
}

label input[type="checkbox"] {
    margin-right: 8px;
}

label:hover {
    background-color: #f0f0f0 !important;
}

label input[type="checkbox"]:checked + span {
    font-weight: bold;
    color: #d63638;
}
</style>