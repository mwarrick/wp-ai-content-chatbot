<?php
/**
 * Plugin Name: AI Content Chatbot
 * Plugin URI: https://warrick.net
 * Description: An intelligent chatbot that reads and understands your website content to answer visitor questions.
 * Version: 1.7.3
 * Author: Mark Warrick
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_CHATBOT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AI_CHATBOT_VERSION', '1.7.3');

class AI_Content_Chatbot {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_test_chatbot_connection', array($this, 'test_chatbot_connection'));
        add_action('wp_ajax_nopriv_test_chatbot_connection', array($this, 'test_chatbot_connection'));
        add_action('wp_ajax_get_claude_models', array($this, 'get_claude_models'));
        add_action('wp_ajax_chatbot_query', array($this, 'handle_chatbot_query'));
        add_action('wp_ajax_nopriv_chatbot_query', array($this, 'handle_chatbot_query'));
        add_action('wp_ajax_clear_content_index', array($this, 'clear_content_index'));
        add_action('wp_ajax_index_all_content', array($this, 'index_all_content'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        $this->create_database_tables();
    }
    
    public function activate() {
        $this->create_database_tables();
        $this->set_default_options();
    }
    
    // Updated to add new columns for location, tags, and keywords
    public function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_type varchar(20) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            url varchar(255) NOT NULL,
            location varchar(100) DEFAULT '',
            tags text,
            keywords text,
            indexed_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        add_option('ai_chatbot_enabled', '1');
        add_option('ai_chatbot_position', 'bottom-right');
        add_option('ai_chatbot_api_key', '');
        add_option('ai_chatbot_model', 'claude-3-5-sonnet-20241022');
        add_option('ai_chatbot_welcome_message', 'Hi! How can I help you today?');
        add_option('ai_chatbot_placeholder', 'Type your question here...');
        add_option('ai_chatbot_primary_color', '#007cba');
        add_option('ai_chatbot_system_prompt', "You are a helpful chatbot for the [SITE_NAME] website. Your primary goal is to help users find bike tours based on their geographic location and skill level. Use the following website content, and ONLY this content, to answer the user's question. You MUST include links to the relevant pages in your response if they are provided. If the content doesn't contain relevant information, politely state that you cannot help with that specific query and suggest they browse the website. DO NOT invent information or link to external websites.

Website Content:
[RELEVANT_CONTENT]");
        add_option('ai_chatbot_excluded_keywords', '');
        add_option('ai_chatbot_window_title', 'Chat with us');
        add_option('ai_chatbot_window_width', '350');
        add_option('ai_chatbot_window_height', '450');
        add_option('ai_chatbot_title_height', '60');
        add_option('ai_chatbot_reply_height', '300');
        add_option('ai_chatbot_input_spacing', '20');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'AI Chatbot',
            'AI Chatbot',
            'manage_options',
            'ai-chatbot',
            array($this, 'admin_page'),
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            'ai-chatbot',
            'Settings',
            'Settings',
            'manage_options',
            'ai-chatbot-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'ai-chatbot',
            'Chat Window',
            'Chat Window',
            'manage_options',
            'ai-chatbot-window',
            array($this, 'chat_window_page')
        );
        
        add_submenu_page(
            'ai-chatbot',
            'Model Selector',
            'Model Selector',
            'manage_options',
            'ai-chatbot-models',
            array($this, 'model_selector_page')
        );
        
        add_submenu_page(
            'ai-chatbot',
            'Content Index',
            'Content Index',
            'manage_options',
            'ai-chatbot-content',
            array($this, 'content_index_page')
        );

        add_submenu_page(
            'ai-chatbot',
            'Content Search',
            'Content Search',
            'manage_options',
            'ai-chatbot-content-search',
            array($this, 'search_content_page')
        );

        add_submenu_page(
            'ai-chatbot',
            'Excluded Keywords',
            'Excluded Keywords',
            'manage_options',
            'ai-chatbot-excluded-keywords',
            array($this, 'excluded_keywords_page')
        );

        // Hidden page for editing indexed content
        add_submenu_page(
            null,
            'Edit Indexed Content',
            'Edit Indexed Content',
            'manage_options',
            'ai-chatbot-edit-indexed-content',
            array($this, 'edit_indexed_content_page')
        );
    }
    
    public function content_index_page() {
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
        
        <script>
        jQuery(document).ready(function($) {
            $('#index-all-content').click(function() {
                var button = $(this);
                var progress = $('#indexing-progress');
                var progressBar = $('#progress-bar');
                var progressText = $('#progress-text');
                
                button.prop('disabled', true).text('Indexing...');
                progress.show();
                progressBar.css('width', '0%');
                progressText.text('0%');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'index_all_content',
                        nonce: '<?php echo wp_create_nonce('ai_chatbot_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        progressBar.css('width', '100%');
                        progressText.text('100%');
                        
                        setTimeout(function() {
                            alert('Content indexed successfully!');
                            location.reload();
                        }, 1000);
                    },
                    error: function(xhr, status, error) {
                        alert('Error indexing content: ' + error);
                        console.error('Indexing error:', error);
                    },
                    complete: function() {
                        button.prop('disabled', false).text('📚 Index All Content');
                        setTimeout(function() {
                            progress.hide();
                        }, 2000);
                    }
                });
            });
            
            $('#clear-index').click(function() {
                if (!confirm('Are you sure you want to clear the entire content index? This action cannot be undone.')) {
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Clearing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clear_content_index',
                        nonce: '<?php echo wp_create_nonce('ai_chatbot_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        alert('Content index cleared successfully!');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Error clearing index: ' + error);
                        console.error('Clear index error:', error);
                    },
                    complete: function() {
                        button.prop('disabled', false).text('🗑️ Clear Index');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function search_content_page() {
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
        <?php
    }
    
    private function get_indexed_content_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    private function get_last_index_date() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 'Never';
        }
        
        $last_date = $wpdb->get_var("SELECT MAX(indexed_date) FROM $table_name");
        return $last_date ? date('F j, Y g:i a', strtotime($last_date)) : 'Never';
    }
    
    private function display_indexed_content($per_page, $offset) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<tr><td colspan="6">No content indexed yet. Click "Index All Content" to start.</td></tr>';
            return;
        }
        
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY indexed_date DESC LIMIT %d OFFSET %d", $per_page, $offset));
        
        if (empty($results)) {
            echo '<tr><td colspan="6">No content indexed yet. Click "Index All Content" to start.</td></tr>';
            return;
        }
        
        foreach ($results as $result) {
            echo '<tr>';
            echo '<td>' . esc_html($result->title) . '</td>';
            echo '<td>' . esc_html(ucfirst($result->post_type)) . '</td>';
            echo '<td>' . esc_html($result->location) . '</td>';
            echo '<td>' . esc_html($result->tags) . '</td>';
            echo '<td>' . esc_html($result->keywords) . '</td>';
            echo '<td>' . esc_html(date('M j, Y', strtotime($result->indexed_date))) . '</td>';
            echo '<td><a href="' . esc_url(admin_url('admin.php?page=ai-chatbot-edit-indexed-content&post_id=' . $result->post_id)) . '">Edit</a></td>';
            echo '</tr>';
        }
    }
    
    // Updated to extract and store location, tags, and keywords
    public function index_all_content() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'ai_chatbot_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        $posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $indexed_count = 0;
        
        foreach ($posts as $post) {
            $content = strip_tags($post->post_content);
            $content = wp_strip_all_tags($content);
            $content = trim(preg_replace('/\s+/', ' ', $content));

            // Extract location from URL structure
            $url = get_permalink($post->ID);
            $location = '';
            if (strpos($url, '/great-rides/') !== false) {
                $path_parts = explode('/', trim(wp_make_link_relative($url), '/'));
                if (isset($path_parts[1])) {
                    $location_slug = $path_parts[1];
                    // Example: "los-angeles-county-guided-mountain-biking-gtours" -> "los-angeles-county"
                    if (strpos($location_slug, '-county-') !== false) {
                         $location = str_replace('-county-', ' County', $location_slug);
                         $location = ucwords(str_replace('-', ' ', $location));
                    } else {
                         $location = ucwords(str_replace('-', ' ', $location_slug));
                    }
                }
            }

            // Get tags and convert to a comma-separated string
            $post_tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
            $tags_string = implode(', ', $post_tags);
            
            // Extract top 10 keywords from content
            $keywords_string = $this->get_top_keywords($content);
            
            if (strlen($content) > 50) {
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'post_id' => $post->ID,
                        'post_type' => $post->post_type,
                        'title' => $post->post_title,
                        'content' => $content,
                        'url' => $url,
                        'location' => $location,
                        'tags' => $tags_string,
                        'keywords' => $keywords_string,
                        'indexed_date' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                );
                
                if ($result !== false) {
                    $indexed_count++;
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => "Successfully indexed {$indexed_count} items",
            'indexed_count' => $indexed_count
        ));
    }

    /**
     * Extracts and returns the top 10 most used keywords from a string of content.
     * @param string $content The content to analyze.
     * @return string A comma-separated string of the top keywords.
     */
    private function get_top_keywords($content) {
        // A list of common English stop words
        $stop_words = array(
            'a','about','above','after','again','against','all','am','an','and','any','are','aren\'t','as','at','be','because',
            'been','before','being','below','between','both','but','by','can\'t','cannot','could','couldn\'t','did','didn\'t','do',
            'does','doesn\'t','doing','don\'t','down','during','each','few','for','from','further','had','hadn\'t','has','hasn\'t',
            'have','haven\'t','having','he','he\'d','he\'ll','he\'s','her','here','here\'s','hers','herself','him','himself','his',
            'how','how\'s','i','i\'d','i\'ll','i\'m','i\'ve','if','in','into','is','isn\'t','it','it\'s','its','itself','let\'s',
            'me','more','most','mustn\'t','my','myself','no','nor','not','of','off','on','once','only','or','other','ought','our',
            'ours','ourselves','out','over','own','same','shan\'t','she','she\'d','she\'ll','she\'s','should','shouldn\'t','so',
            'some','such','than','that','that\'s','the','their','theirs','them','themselves','then','there','there\'s','these',
            'they','they\'d','they\'ll','they\'re','they\'ve','this','those','through','to','too','under','until','up','very',
            'was','wasn\'t','we','we\'d','we\'ll','we\'re','we\'ve','were','weren\'t','what','what\'s','when','when\'s','where',
            'where\'s','which','while','who','who\'s','whom','why','why\'s','with','won\'t','would','wouldn\'t','you','you\'d',
            'you\'ll','you\'re','you\'ve','your','yours','yourself','yourselves'
        );

        $excluded_keywords = get_option('ai_chatbot_excluded_keywords', '');
        $excluded_array = explode(',', $excluded_keywords);
        $excluded_array = array_map('trim', $excluded_array);
        $stop_words = array_merge($stop_words, $excluded_array);

        // Clean and tokenize content
        $content = strtolower($content);
        $words = str_word_count($content, 1);
        
        // Count word frequencies, ignoring stop words and single-letter words
        $word_counts = array();
        foreach ($words as $word) {
            if (strlen($word) > 2 && !in_array($word, $stop_words)) {
                $word_counts[$word] = isset($word_counts[$word]) ? $word_counts[$word] + 1 : 1;
            }
        }
        
        // Sort keywords by frequency
        arsort($word_counts);
        
        // Get the top 10 keywords
        $top_keywords = array_slice($word_counts, 0, 10);
        
        return implode(', ', array_keys($top_keywords));
    }

    public function clear_content_index() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'ai_chatbot_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        if ($result !== false) {
            wp_send_json_success('Content index cleared successfully');
        } else {
            wp_send_json_error('Failed to clear content index');
        }
    }
    
    public function model_selector_page() {
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
        
        <script>
        jQuery(document).ready(function($) {
            console.log('Model Selector Page: JavaScript loaded');
            
            function showResult(message, type) {
                const colors = {
                    success: { bg: '#d4edda', border: '#c3e6cb', text: '#155724' },
                    error: { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
                    info: { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460' }
                };
                
                const color = colors[type] || colors.info;
                $('#results-content').html(message);
                $('#results').css({
                    'background-color': color.bg,
                    'border-color': color.border,
                    'color': color.text
                });
            }
            
            $('#get-models').click(function() {
                showResult('🔄 Fetching available Claude models from API...', 'info');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'get_claude_models' },
                    success: function(response) {
                        if (response && response.success) {
                            let html = '<strong>✅ Available Models Found:</strong><br><br>';
                            if (response.data.models && response.data.models.length > 0) {
                                html += '<select id="model-selector" style="width: 100%; padding: 10px; margin: 10px 0; font-size: 16px;">';
                                response.data.models.forEach(function(model) {
                                    const selected = model.id === '<?php echo esc_js(get_option('ai_chatbot_model', '')); ?>' ? ' selected' : '';
                                    html += '<option value="' + model.id + '"' + selected + '>' + model.id + '</option>';
                                });
                                html += '</select><br>';
                                html += '<button type="button" id="save-model" class="button button-primary" style="margin-top: 10px; padding: 10px 20px;">💾 Save Selected Model</button>';
                                
                                if (response.data.note) {
                                    html += '<br><br><small><em>Note: ' . response.data.note . '</em></small>';
                                }
                                html += '<br><small>Total models: ' . response.data.models.length . '</small>';
                            } else {
                                html += '<em>No models found in API response.</em>';
                            }
                            showResult(html, 'success');
                        } else {
                            const errorMsg = response && response.data ? response.data : 'Unknown error occurred';
                            showResult('<strong>❌ Failed to get models:</strong><br>' + errorMsg, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showResult('<strong>❌ Request failed:</strong><br>Status: ' + status + '<br>Error: ' + error + '<br>HTTP Code: ' . xhr.status, 'error');
                    }
                });
            });
            
            $(document).on('click', '#save-model', function() {
                const selectedModel = $('#model-selector').val();
                if (!selectedModel) {
                    alert('Please select a model first.');
                    return;
                }
                
                showResult('💾 Saving model: ' + selectedModel + '...', 'info');
                
                window.location.href = '<?php echo admin_url('admin.php?page=ai-chatbot-models&save_model='); ?>' + encodeURIComponent(selectedModel);
            });
            
            $('#test-api').click(function() {
                showResult('🌐 Testing Claude API with current model...', 'info');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'test_chatbot_connection' },
                    success: function(response) {
                        if (response && response.success) {
                            const model = response.data.model_used || 'Unknown';
                            const text = response.data.response_text || 'No response';
                            showResult('✅ <strong>Claude API: SUCCESS</strong><br>Model: ' + model + '<br>Response: ' + text, 'success');
                        } else {
                            const errorMsg = response && response.data ? response.data : 'Unknown error';
                            showResult('❌ <strong>Claude API: FAILED</strong><br>Error: ' + errorMsg, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showResult('❌ <strong>Claude API: REQUEST FAILED</strong><br>Status: ' + status + '<br>Error: ' + error + '<br>HTTP Code: ' . xhr.status, 'error');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function admin_page() {
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
        
        <script>
        jQuery(document).ready(function($) {
            function showResult(message, type) {
                const colors = {
                    success: { bg: '#d1fae5', border: '#10b981', text: '#065f46' },
                    error: { bg: '#fee2e2', border: '#ef4444', text: '#991b1b' },
                    info: { bg: '#dbeafe', border: '#3b82f6', text: '#1e3a8a' }
                };
                
                const color = colors[type] || colors.info;
                $('#result-text').html(message);
                $('#test-results').css({
                    'background-color': color.bg,
                    'border-color': color.border,
                    'color': color.text
                });
            }
            
            $('#test-wp-ajax').click(function() {
                showResult('🔄 Testing WordPress AJAX...', 'info');
                
                $.post(ajaxurl, {
                    action: 'heartbeat',
                    data: { test: 'basic' }
                }).done(function(response) {
                    showResult('✅ <strong>WordPress AJAX: SUCCESS</strong><br>Your WordPress can handle AJAX requests properly.', 'success');
                }).fail(function(xhr, status, error) {
                    showResult('❌ <strong>WordPress AJAX: FAILED</strong><br>Error: ' + status + ' - ' + error + '<br>HTTP Code: ' . xhr.status, 'error');
                });
            });
            
            $('#test-claude-api').click(function() {
                showResult('🔄 Testing Claude API connection...', 'info');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'test_chatbot_connection' },
                    success: function(response) {
                        if (response && response.success) {
                            const model = response.data.model_used || 'Unknown';
                            const text = response.data.response_text || 'No response';
                            showResult('✅ <strong>Claude API: SUCCESS</strong><br>Model: ' + model + '<br>Response: ' + text, 'success');
                        } else {
                            const errorMsg = response && response.data ? response.data : 'Unknown error';
                            showResult('❌ <strong>Claude API: FAILED</strong><br>Error: ' + errorMsg, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showResult('❌ <strong>Claude API: REQUEST FAILED</strong><br>Status: ' + status + '<br>Error: ' + error + '<br>HTTP Code: ' . xhr.status, 'error');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function settings_page() {
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
        <?php
    }

    public function chat_window_page() {
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
        <?php
    }

    public function excluded_keywords_page() {
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
        <?php
    }

    public function edit_indexed_content_page() {
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
        <?php
    }

    public function get_claude_models() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $api_key = get_option('ai_chatbot_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error('No API key configured. Please add your Claude API key in Settings first.');
        }
        
        $response = wp_remote_get('https://api.anthropic.com/v1/models', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to get models: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code === 200 && isset($data['data'])) {
            wp_send_json_success(array(
                'models' => $data['data'],
                'total' => count($data['data'])
            ));
        } else {
            $fallback_models = array(
                array('id' => 'claude-3-5-sonnet-20241022'),
                array('id' => 'claude-3-5-haiku-20241022'),
                array('id' => 'claude-3-opus-20240229'),
                array('id' => 'claude-3-sonnet-20240229'),
                array('id' => 'claude-3-haiku-20240307'),
                array('id' => 'claude-2.1'),
                array('id' => 'claude-2.0'),
                array('id' => 'claude-instant-1.2')
            );
            
            wp_send_json_success(array(
                'models' => $fallback_models,
                'total' => count($fallback_models),
                'note' => 'Using fallback model list. API models endpoint returned HTTP ' . $response_code
            ));
        }
    }
    
    public function test_chatbot_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $api_key = get_option('ai_chatbot_api_key', '');
        $selected_model = get_option('ai_chatbot_model', 'claude-3-5-sonnet-20241022');
        
        if (empty($api_key)) {
            wp_send_json_error('No API key configured. Please add your Claude API key in Settings.');
        }
        
        if (strlen($api_key) < 50 || strpos($api_key, 'sk-ant-') !== 0) {
            wp_send_json_error('Invalid API key format. Claude API keys should start with "sk-ant-" and be 100+ characters long.');
        }
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $selected_model,
                'max_tokens' => 50,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Respond with: Connection test successful'
                    )
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code === 200 && isset($data['content'][0]['text'])) {
            wp_send_json_success(array(
                'message' => 'API connection successful with model: ' . $selected_model,
                'response_text' => $data['content'][0]['text'],
                'model_used' => $selected_model
            ));
        } else {
            $error_msg = 'API Error (HTTP ' . $response_code . ')';
            if (isset($data['error']['message'])) {
                $error_msg .= ': ' . $data['error']['message'];
                
                if (strpos($data['error']['message'], 'model') !== false) {
                    $error_msg .= '<br><br><strong>Model Error:</strong> Try using the Model Selector to find a working model.';
                }
            }
            wp_send_json_error($error_msg);
        }
    }
    
    // Updated to use the refined search logic
    public function handle_chatbot_query() {
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_frontend_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $user_message = sanitize_text_field($_POST['message']);
        
        if (empty($user_message)) {
            wp_send_json_error('No message provided');
        }
        
        $api_key = get_option('ai_chatbot_api_key', '');
        $model = get_option('ai_chatbot_model', 'claude-3-5-sonnet-20241022');
        
        if (empty($api_key)) {
            wp_send_json_success(array(
                'response' => 'Sorry, the chatbot is not configured yet. Please contact the site administrator.'
            ));
        }
        
        // Search indexed content with new logic
        $relevant_content = $this->search_indexed_content($user_message);
        
        // Retrieve the custom system prompt from options
        $custom_prompt = get_option('ai_chatbot_system_prompt');
        $site_name = get_bloginfo('name');

        // Replace placeholders in the custom prompt
        $system_prompt = str_replace(
            array('[SITE_NAME]', '[RELEVANT_CONTENT]'),
            array($site_name, $relevant_content),
            $custom_prompt
        );

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'max_tokens' => 300,
                'system' => $system_prompt,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $user_message
                    )
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_success(array(
                'response' => 'I\'m sorry, I\'m having trouble connecting right now. Please try again later.'
            ));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code === 200 && isset($data['content'][0]['text'])) {
            wp_send_json_success(array(
                'response' => $data['content'][0]['text']
            ));
        } else {
            wp_send_json_success(array(
                'response' => 'I\'m sorry, I encountered an error while processing your request. Please try again.'
            ));
        }
    }
    
    // Updated to use location and tags for a more relevant search
    private function search_indexed_content($query) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return "No indexed content available.";
        }
        
        $search_terms = explode(' ', strtolower($query));
        $where_conditions = array();
        $location_filter = null;
        $tags_filter = array();

        // Detect location and tags from the user's query
        $lower_query = strtolower($query);

        // Simple keyword mapping for locations and tags
        if (strpos($lower_query, 'los angeles') !== false || strpos($lower_query, 'la') !== false) {
            $location_filter = 'Los Angeles County';
        }
        if (strpos($lower_query, 'orange county') !== false) {
            $location_filter = 'Orange County';
        }
        
        if (strpos($lower_query, 'beginner') !== false) {
            $tags_filter[] = 'beginner';
        }
        if (strpos($lower_query, 'advanced') !== false) {
            $tags_filter[] = 'advanced';
        }
        if (strpos($lower_query, 'intermediate') !== false) {
            $tags_filter[] = 'intermediate';
        }

        // Add 'force-search' words that should always be included
        $force_search_words = array('contact', 'about', 'services', 'blog', 'home', 'powerbi');

        // Build base WHERE clause for keywords, content and title
        foreach ($search_terms as $term) {
            $term = trim($term);
            if (strlen($term) > 2 || in_array($term, $force_search_words)) {
                $safe_term = $wpdb->esc_like($term);
                // Search in keywords, title, and content
                $where_conditions[] = $wpdb->prepare("(LOWER(keywords) LIKE %s OR LOWER(title) LIKE %s OR LOWER(content) LIKE %s)", '%' . $safe_term . '%', '%' . $safe_term . '%', '%' . $safe_term . '%');
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Execute the first, most specific search
        if (!empty($where_conditions) && $location_filter && !empty($tags_filter)) {
            $sql = "SELECT title, url, content FROM $table_name WHERE $where_clause AND location = %s AND (" . implode(' AND ', array_map(function($t) use ($wpdb) { return $wpdb->prepare('tags LIKE %s', '%' . $wpdb->esc_like($t) . '%'); }, $tags_filter)) . ") ORDER BY indexed_date DESC LIMIT 5";
            $results = $wpdb->get_results($wpdb->prepare($sql, $location_filter));
        } elseif (!empty($where_conditions) && $location_filter) {
            $sql = "SELECT title, url, content FROM $table_name WHERE $where_clause AND location = %s ORDER BY indexed_date DESC LIMIT 5";
            $results = $wpdb->get_results($wpdb->prepare($sql, $location_filter));
        } elseif (!empty($where_conditions) && !empty($tags_filter)) {
             $sql = "SELECT title, url, content FROM $table_name WHERE $where_clause AND (" . implode(' AND ', array_map(function($t) use ($wpdb) { return $wpdb->prepare('tags LIKE %s', '%' . $wpdb->esc_like($t) . '%'); }, $tags_filter)) . ") ORDER BY indexed_date DESC LIMIT 5";
             $results = $wpdb->get_results($sql);
        } elseif (!empty($where_conditions)) {
            $sql = "SELECT title, url, content FROM $table_name WHERE $where_clause ORDER BY indexed_date DESC LIMIT 5";
            $results = $wpdb->get_results($sql);
        } else {
            // No specific keywords, fall back to simple, broad search.
            $sql = "SELECT title, url, content FROM $table_name WHERE title LIKE %s OR content LIKE %s ORDER BY indexed_date DESC LIMIT 5";
            $results = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($query) . '%', '%' . $wpdb->esc_like($query) . '%'));
        }
        
        if (empty($results)) {
            // Secondary, broader search if primary search fails
            $broad_where_conditions = array();
            foreach ($search_terms as $term) {
                $term = trim($term);
                if (strlen($term) > 2 || in_array($term, $force_search_words)) {
                    $safe_term = $wpdb->esc_like($term);
                    $broad_where_conditions[] = $wpdb->prepare("(LOWER(keywords) LIKE %s OR LOWER(title) LIKE %s OR LOWER(content) LIKE %s)", '%' . $safe_term . '%', '%' . $safe_term . '%', '%' . $safe_term . '%');
                }
            }
            if (!empty($broad_where_conditions)) {
                $broad_where_clause = implode(' OR ', $broad_where_conditions);
                $sql = "SELECT title, url, content FROM $table_name WHERE $broad_where_clause ORDER BY indexed_date DESC LIMIT 5";
                $results = $wpdb->get_results($sql);
            }
        }
        
        if (empty($results)) {
            return "No relevant content found.";
        }
        
        // Format content with Markdown links for the AI to use
        $content_summary = "";
        foreach ($results as $result) {
            $content_summary .= "[" . $result->title . "](" . $result->url . ")\n";
            $content_summary .= "Content: " . substr($result->content, 0, 500) . "...\n\n";
        }
        
        return $content_summary;
    }
    
    public function enqueue_frontend_scripts() {
        if (is_admin() || get_option('ai_chatbot_enabled', '1') != '1') {
            return;
        }
        
        wp_enqueue_script('jquery');

        // Enqueue frontend assets
        wp_enqueue_style(
            'ai-chatbot-frontend',
            AI_CHATBOT_PLUGIN_URL . 'assets/css/chatbot.css',
            array(),
            AI_CHATBOT_VERSION
        );

        wp_enqueue_script(
            'ai-chatbot-frontend',
            AI_CHATBOT_PLUGIN_URL . 'assets/js/chatbot.js',
            array('jquery'),
            AI_CHATBOT_VERSION,
            true
        );

        // Localize data for AJAX
        wp_localize_script('ai-chatbot-frontend', 'AIChatbot', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_frontend_nonce')
        ));

        add_action('wp_footer', array($this, 'render_chatbot_widget'), 999);
    }
    
    public function render_chatbot_widget() {
        $primary_color = get_option('ai_chatbot_primary_color', '#007cba');
        $welcome_message = get_option('ai_chatbot_welcome_message', 'Hi! How can I help you today?');
        $placeholder = get_option('ai_chatbot_placeholder', 'Type your question here...');
        $window_title = get_option('ai_chatbot_window_title', 'Chat with us');
        $window_width = get_option('ai_chatbot_window_width', '350');
        $window_height = get_option('ai_chatbot_window_height', '450');
        $title_height = get_option('ai_chatbot_title_height', '60');
        $reply_height = get_option('ai_chatbot_reply_height', '300');
        $input_spacing = get_option('ai_chatbot_input_spacing', '20');

        ?>
        <div id="ai-chatbot-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 999999; --ai-chatbot-primary-color: <?php echo esc_attr($primary_color); ?>;">
            <div id="ai-chatbot-toggle" style="width: 60px; height: 60px; border-radius: 50%; background: <?php echo esc_attr($primary_color); ?>; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                💬
            </div>
            <div id="ai-chatbot-container" style="display: none; position: absolute; bottom: 80px; right: 0; width: <?php echo esc_attr($window_width); ?>px; height: <?php echo esc_attr($window_height); ?>px; background: white; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); overflow: hidden;">
                <!-- Header -->
                <div style="padding: 20px; border-bottom: 1px solid #eee; background: <?php echo esc_attr($primary_color); ?>; color: white; height: <?php echo esc_attr($title_height); ?>px; display: flex; align-items: center; justify-content: space-between;">
                    <h3 class="chat-title" style="margin: 0; font-size: 16px;"><?php echo esc_html($window_title); ?></h3>
                    <button id="close-chat" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; padding: 0; width: 24px; height: 24px;">×</button>
                </div>
                
                <!-- Messages Area -->
                <div id="chat-messages" style="padding: 20px; height: <?php echo esc_attr($reply_height); ?>px; overflow-y: auto; scroll-behavior: smooth;">
                    <div class="chat-message bot">
                        <div class="message-bubble bot"><?php echo esc_html($welcome_message); ?></div>
                    </div>
                </div>
                
                <!-- Typing Indicator -->
                <div id="typing-indicator" class="typing-indicator">
                    Bot is typing...
                </div>
                
                <!-- Input Area -->
                <div id="ai-chatbot-input-container" style="margin-bottom: <?php echo esc_attr($input_spacing); ?>px;">
                    <input type="text" id="chat-input" placeholder="<?php echo esc_attr($placeholder); ?>" style="flex: 1; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 14px;">
                    <button id="send-button" type="button" style="margin-left: 10px;">
                        ➤
                    </button>
                </div>
            </div>
        </div>
        
        <?php
    }
}

// Initialize the plugin
new AI_Content_Chatbot();
?>
