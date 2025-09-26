<?php
/**
 * Excluded Keywords page template
 */

// Handle keyword removal
if (isset($_POST['remove_keyword'])) {
    $keyword_to_remove = sanitize_text_field($_POST['remove_keyword']);
    $current_excluded_keywords = get_option('ai_chatbot_excluded_keywords', '');
    $currently_excluded_array = !empty($current_excluded_keywords) ? array_map('trim', explode(',', $current_excluded_keywords)) : array();
    
    // Remove the keyword
    $updated_excluded_array = array_filter($currently_excluded_array, function($keyword) use ($keyword_to_remove) {
        return $keyword !== $keyword_to_remove;
    });
    
    $excluded_keywords_string = implode(', ', $updated_excluded_array);
    update_option('ai_chatbot_excluded_keywords', $excluded_keywords_string);
    
    echo '<div class="notice notice-success"><p>Keyword "' . esc_html($keyword_to_remove) . '" removed from exclusions!</p></div>';
}

// Handle form submission
if (isset($_POST['submit'])) {
    // Start with currently excluded keywords to preserve them
    $current_excluded_keywords = get_option('ai_chatbot_excluded_keywords', '');
    $currently_excluded_array = !empty($current_excluded_keywords) ? array_map('trim', explode(',', $current_excluded_keywords)) : array();
    
    $excluded_keywords = $currently_excluded_array;
    
    // Get keywords from checkboxes (these are NEW exclusions)
    if (isset($_POST['excluded_keyword_checkboxes']) && is_array($_POST['excluded_keyword_checkboxes'])) {
        $new_exclusions = array_map('sanitize_text_field', $_POST['excluded_keyword_checkboxes']);
        $excluded_keywords = array_merge($excluded_keywords, $new_exclusions);
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
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <p style="margin: 0;"><strong>Select keywords to exclude from search:</strong></p>
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" onclick="sortKeywords('count-desc')" class="button button-small" title="Sort by count (high to low)">Count ↓</button>
                                    <button type="button" onclick="sortKeywords('count-asc')" class="button button-small" title="Sort by count (low to high)">Count ↑</button>
                                    <button type="button" onclick="sortKeywords('alpha-asc')" class="button button-small" title="Sort alphabetically A-Z">A-Z</button>
                                    <button type="button" onclick="sortKeywords('alpha-desc')" class="button button-small" title="Sort alphabetically Z-A">Z-A</button>
                                </div>
                            </div>
                            <div id="keywords-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 5px;">
                                <?php 
                                $available_keywords = 0;
                                foreach ($all_keywords as $keyword => $count): 
                                    // Skip keywords that are already excluded
                                    if (in_array($keyword, $excluded_array)) {
                                        continue;
                                    }
                                    $available_keywords++;
                                ?>
                                    <label class="keyword-item" data-keyword="<?php echo esc_attr($keyword); ?>" data-count="<?php echo $count; ?>" style="display: flex; align-items: center; padding: 5px; background: white; border: 1px solid #e0e0e0; border-radius: 3px;">
                                        <input type="checkbox" 
                                               name="excluded_keyword_checkboxes[]" 
                                               value="<?php echo esc_attr($keyword); ?>"
                                               style="margin-right: 8px;">
                                        <span style="flex: 1; font-size: 13px;">
                                            <?php echo esc_html($keyword); ?>
                                            <span style="color: #666; font-size: 11px;">(<?php echo $count; ?>)</span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                                
                                <?php if ($available_keywords === 0): ?>
                                    <p id="no-keywords-message" style="color: #666; font-style: italic; grid-column: 1 / -1; text-align: center; padding: 20px;">
                                        All keywords are currently excluded. Use the "Additional Keywords" field below to add more, or remove some from the excluded list to see them here.
                                    </p>
                                <?php endif; ?>
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
                    <th scope="row">Currently Excluded Keywords</th>
                    <td>
                        <div style="background: #f0f0f0; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;">
                            <?php if (!empty($excluded_array)): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <strong style="color: #0073aa;">Excluded Keywords (<?php echo count($excluded_array); ?>)</strong>
                                    <div style="display: flex; gap: 5px;">
                                        <button type="button" onclick="sortExcludedKeywords('alpha-asc')" class="button button-small" title="Sort alphabetically A-Z">A-Z</button>
                                        <button type="button" onclick="sortExcludedKeywords('alpha-desc')" class="button button-small" title="Sort alphabetically Z-A">Z-A</button>
                                    </div>
                                </div>
                                <div id="excluded-keywords-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 5px;">
                                    <?php foreach ($excluded_array as $keyword): ?>
                                        <div class="excluded-keyword-item" data-keyword="<?php echo esc_attr($keyword); ?>" style="display: flex; align-items: center; background: white; padding: 5px; border: 1px solid #e0e0e0; border-radius: 3px;">
                                            <span style="flex: 1; font-size: 13px; color: #0073aa; font-weight: bold;">
                                                <?php echo esc_html($keyword); ?>
                                            </span>
                                            <form method="post" style="margin: 0; padding: 0;" onsubmit="return confirm('Remove keyword \'<?php echo esc_js($keyword); ?>\' from exclusions?')">
                                                <input type="hidden" name="remove_keyword" value="<?php echo esc_attr($keyword); ?>">
                                                <button type="submit" style="background: #dc3545; color: white; border: none; padding: 2px 6px; border-radius: 2px; font-size: 11px; cursor: pointer;" title="Remove this keyword">×</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <em style="color: #666;">No keywords are currently excluded.</em>
                            <?php endif; ?>
                        </div>
                        <p class="description">
                            Currently excluded keywords. Click the <strong>×</strong> button to remove a keyword from exclusions.
                            <br><em>Note: Removed keywords will appear back in the checkbox list above.</em>
                        </p>
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

<script type="text/javascript">
function sortKeywords(sortType) {
    const container = document.getElementById('keywords-container');
    const items = Array.from(container.querySelectorAll('.keyword-item'));
    const noMessage = document.getElementById('no-keywords-message');
    
    if (items.length === 0) return;
    
    items.sort((a, b) => {
        const keywordA = a.dataset.keyword.toLowerCase();
        const keywordB = b.dataset.keyword.toLowerCase();
        const countA = parseInt(a.dataset.count);
        const countB = parseInt(b.dataset.count);
        
        switch(sortType) {
            case 'count-desc':
                return countB - countA;
            case 'count-asc':
                return countA - countB;
            case 'alpha-asc':
                return keywordA.localeCompare(keywordB);
            case 'alpha-desc':
                return keywordB.localeCompare(keywordA);
            default:
                return 0;
        }
    });
    
    // Clear container and re-append sorted items
    container.innerHTML = '';
    items.forEach(item => container.appendChild(item));
    
    // Re-add no message if it exists
    if (noMessage) {
        container.appendChild(noMessage);
    }
    
    // Visual feedback - highlight active sort button
    document.querySelectorAll('button[onclick^="sortKeywords"]').forEach(btn => {
        btn.style.backgroundColor = '';
        btn.style.color = '';
    });
    event.target.style.backgroundColor = '#0073aa';
    event.target.style.color = 'white';
}

function sortExcludedKeywords(sortType) {
    const container = document.getElementById('excluded-keywords-container');
    const items = Array.from(container.querySelectorAll('.excluded-keyword-item'));
    
    if (items.length === 0) return;
    
    items.sort((a, b) => {
        const keywordA = a.dataset.keyword.toLowerCase();
        const keywordB = b.dataset.keyword.toLowerCase();
        
        switch(sortType) {
            case 'alpha-asc':
                return keywordA.localeCompare(keywordB);
            case 'alpha-desc':
                return keywordB.localeCompare(keywordA);
            default:
                return 0;
        }
    });
    
    // Clear container and re-append sorted items
    container.innerHTML = '';
    items.forEach(item => container.appendChild(item));
    
    // Visual feedback - highlight active sort button
    document.querySelectorAll('button[onclick^="sortExcludedKeywords"]').forEach(btn => {
        btn.style.backgroundColor = '';
        btn.style.color = '';
    });
    event.target.style.backgroundColor = '#0073aa';
    event.target.style.color = 'white';
}

// Initialize with count descending sort for available keywords
document.addEventListener('DOMContentLoaded', function() {
    const countDescButton = document.querySelector('button[onclick="sortKeywords(\'count-desc\')"]');
    if (countDescButton) {
        countDescButton.click();
    }
});
</script>

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

/* Sorting button styles */
.button.button-small {
    font-size: 11px;
    height: auto;
    line-height: 1.4;
    padding: 3px 8px;
    transition: all 0.2s ease;
}

.button.button-small:hover {
    background-color: #0073aa;
    color: white;
    border-color: #0073aa;
}

.button.button-small:focus {
    box-shadow: 0 0 0 1px #0073aa;
}
</style>