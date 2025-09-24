<?php
/**
 * Plugin Name: AI Content Chatbot
 * Plugin URI: https://warrick.net
 * Description: An intelligent chatbot that reads and understands your website content to answer visitor questions.
 * Version: 1.8.0
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
define('AI_CHATBOT_VERSION', '1.8.0');

class AI_Content_Chatbot {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_test_chatbot_connection', array($this, 'test_chatbot_connection'));
        add_action('wp_ajax_nopriv_test_chatbot_connection', array($this, 'test_chatbot_connection'));
        add_action('wp_ajax_get_claude_models', array($this, 'get_claude_models'));
        add_action('wp_ajax_chatbot_query', array($this, 'handle_chatbot_query'));
        add_action('wp_ajax_nopriv_chatbot_query', array($this, 'handle_chatbot_query'));
        add_action('wp_ajax_clear_content_index', array($this, 'clear_content_index'));
        add_action('wp_ajax_index_all_content', array($this, 'index_all_content'));
        add_action('wp_ajax_get_log_details', array($this, 'get_log_details'));
        
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
        
        // Create chatbot interactions log table
        $log_table_name = $wpdb->prefix . 'chatbot_interactions';
        
        $log_sql = "CREATE TABLE $log_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_query text NOT NULL,
            ai_response longtext,
            relevant_content longtext,
            error_message text,
            response_time_ms int(11) DEFAULT 0,
            api_model varchar(100),
            user_ip varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            success tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY success (success)
        ) $charset_collate;";
        
        dbDelta($log_sql);
    }
    
    private function set_default_options() {
        add_option('ai_chatbot_enabled', '1');
        add_option('ai_chatbot_position', 'bottom-right');
        add_option('ai_chatbot_api_key', '');
        add_option('ai_chatbot_model', 'claude-3-5-sonnet-20241022');
        add_option('ai_chatbot_welcome_message', 'Hi! How can I help you today?');
        add_option('ai_chatbot_placeholder', 'Type your question here...');
        add_option('ai_chatbot_primary_color', '#007cba');
        add_option('ai_chatbot_system_prompt', "You are a helpful chatbot for the [SITE_NAME] website. 

CRITICAL RULES:
1. Use ONLY the website content provided below to answer questions
2. DO NOT add, invent, or infer any information not explicitly stated in the provided content
3. DO NOT mention specific companies, dates, or details unless they appear in the provided content
4. If the provided content doesn't contain enough information to answer the question, say so clearly
5. You MUST include clickable links to relevant pages in your response when available

The content below includes Markdown links in the format [Page Title](URL). Always include these links in your response when relevant to the user's question.

If the content doesn't contain relevant information, politely state: 'I don't have enough information in the website content to answer that question. Please browse the website or contact us directly for more details.'

Website Content:
[RELEVANT_CONTENT]");
        add_option('ai_chatbot_excluded_keywords', '');
        add_option('ai_chatbot_included_post_types', array('post', 'page'));
        add_option('ai_chatbot_excluded_post_types', array());
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
        
        add_submenu_page(
            'ai-chatbot',
            'Interaction Logs',
            'Interaction Logs',
            'manage_options',
            'ai-chatbot-logs',
            array($this, 'interaction_logs_page')
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
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/content-index.php';
    }

    public function search_content_page() {
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/content-search.php';
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
        
        // Get post types to index from settings
        $included_post_types = get_option('ai_chatbot_included_post_types', array('post', 'page'));
        $excluded_post_types = get_option('ai_chatbot_excluded_post_types', array());
        
        // Filter out excluded post types
        $post_types_to_index = array_diff($included_post_types, $excluded_post_types);
        
        if (empty($post_types_to_index)) {
            wp_send_json_error('No post types selected for indexing');
        }
        
        $posts = get_posts(array(
            'post_type' => $post_types_to_index,
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $indexed_count = 0;
        
        foreach ($posts as $post) {
            $content = strip_tags($post->post_content);
            $content = wp_strip_all_tags($content);
            $content = trim(preg_replace('/\s+/', ' ', $content));

            // Get post URL
            $url = get_permalink($post->ID);
            $location = ''; // Location field kept for backward compatibility but not populated

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
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/model-selector.php';
    }
    
    public function admin_page() {
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/admin-dashboard.php';
    }
    
    public function settings_page() {
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/settings.php';
    }

    public function chat_window_page() {
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/chat-window.php';
    }

    public function excluded_keywords_page() {
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/excluded-keywords.php';
    }
    
    public function interaction_logs_page() {
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/interaction-logs.php';
    }

    public function edit_indexed_content_page() {
        include AI_CHATBOT_PLUGIN_PATH . 'includes/admin/pages/edit-indexed-content.php';
    }
    
    /**
     * Get all unique keywords from indexed content
     */
    public function get_all_keywords() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }
        
        $results = $wpdb->get_results("SELECT keywords FROM $table_name WHERE keywords != ''");
        $all_keywords = array();
        
        foreach ($results as $result) {
            $keywords = explode(', ', $result->keywords);
            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (!empty($keyword)) {
                    $all_keywords[$keyword] = isset($all_keywords[$keyword]) ? $all_keywords[$keyword] + 1 : 1;
                }
            }
        }
        
        // Sort by frequency (most used first)
        arsort($all_keywords);
        
        return $all_keywords;
    }
    
    /**
     * Log chatbot interaction for debugging and analytics
     */
    private function log_chatbot_interaction($user_query, $ai_response = null, $relevant_content = null, $error_message = null, $response_time_ms = 0, $success = true) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_interactions';
        
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $api_model = get_option('ai_chatbot_model', 'claude-3-5-sonnet-20241022');
        
        $wpdb->insert(
            $table_name,
            array(
                'user_query' => $user_query,
                'ai_response' => $ai_response,
                'relevant_content' => $relevant_content,
                'error_message' => $error_message,
                'response_time_ms' => $response_time_ms,
                'api_model' => $api_model,
                'user_ip' => $user_ip,
                'user_agent' => $user_agent,
                'success' => $success ? 1 : 0
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d')
        );
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
    
    public function get_log_details() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'ai_chatbot_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $log_id = intval($_POST['log_id']);
        
        if (empty($log_id)) {
            wp_send_json_error('Invalid log ID');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_interactions';
        
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $log_id));
        
        if (!$log) {
            wp_send_json_error('Log not found');
        }
        
        wp_send_json_success($log);
    }
    
    // Updated to use the refined search logic
    public function handle_chatbot_query() {
        $start_time = microtime(true);
        $user_message = '';
        $relevant_content = '';
        $ai_response = '';
        $error_message = '';
        $success = true;
        
        try {
            // Security check
            if (!wp_verify_nonce($_POST['nonce'], 'chatbot_frontend_nonce')) {
                $error_message = 'Security check failed - invalid nonce';
                $this->log_chatbot_interaction('', '', '', $error_message, 0, false);
                wp_send_json_error('Security check failed');
            }
            
            $user_message = sanitize_text_field($_POST['message']);
            
            if (empty($user_message)) {
                $error_message = 'No message provided';
                $this->log_chatbot_interaction('', '', '', $error_message, 0, false);
                wp_send_json_error('No message provided');
            }
            
            $api_key = get_option('ai_chatbot_api_key', '');
            $model = get_option('ai_chatbot_model', 'claude-3-5-sonnet-20241022');
            
            if (empty($api_key)) {
                $ai_response = 'Sorry, the chatbot is not configured yet. Please contact the site administrator.';
                $this->log_chatbot_interaction($user_message, $ai_response, '', 'No API key configured', 0, true);
                wp_send_json_success(array('response' => $ai_response));
            }
            
            // Search indexed content with new logic
            $relevant_content = $this->search_indexed_content($user_message);
            
            // If no relevant content found, return a helpful message instead of letting AI hallucinate
            if (strpos($relevant_content, 'No relevant content found') !== false || 
                strpos($relevant_content, 'No indexed content available') !== false ||
                trim($relevant_content) === '') {
                $ai_response = 'I don\'t have enough information in the website content to answer that question. Please browse the website or contact us directly for more details.';
                $this->log_chatbot_interaction($user_message, $ai_response, $relevant_content, 'No relevant content found', 0, true);
                wp_send_json_success(array('response' => $ai_response));
            }
            
            // Retrieve the custom system prompt from options
            $custom_prompt = get_option('ai_chatbot_system_prompt');
            $site_name = get_bloginfo('name');

            // Replace placeholders in the custom prompt
            $system_prompt = str_replace(
                array('[SITE_NAME]', '[RELEVANT_CONTENT]'),
                array($site_name, $relevant_content),
                $custom_prompt
            );

            // Make API call with increased timeout and better error handling
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
                'timeout' => 60, // Increased timeout
                'blocking' => true
            ));
            
            $response_time_ms = round((microtime(true) - $start_time) * 1000);
            
            if (is_wp_error($response)) {
                $error_message = 'API request failed: ' . $response->get_error_message();
                $ai_response = 'I\'m sorry, I\'m having trouble connecting right now. Please try again later.';
                $this->log_chatbot_interaction($user_message, $ai_response, $relevant_content, $error_message, $response_time_ms, false);
                wp_send_json_success(array('response' => $ai_response));
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($response_code === 200 && isset($data['content'][0]['text'])) {
                $ai_response = $data['content'][0]['text'];
                $this->log_chatbot_interaction($user_message, $ai_response, $relevant_content, '', $response_time_ms, true);
                wp_send_json_success(array('response' => $ai_response));
            } else {
                $error_message = 'API Error (HTTP ' . $response_code . '): ' . $body;
                $ai_response = 'I\'m sorry, I encountered an error while processing your request. Please try again.';
                $this->log_chatbot_interaction($user_message, $ai_response, $relevant_content, $error_message, $response_time_ms, false);
                wp_send_json_success(array('response' => $ai_response));
            }
            
        } catch (Exception $e) {
            $response_time_ms = round((microtime(true) - $start_time) * 1000);
            $error_message = 'Exception: ' . $e->getMessage();
            $ai_response = 'I\'m sorry, I encountered an unexpected error. Please try again later.';
            $this->log_chatbot_interaction($user_message, $ai_response, $relevant_content, $error_message, $response_time_ms, false);
            wp_send_json_success(array('response' => $ai_response));
        }
    }
    
	// Improved search: phrase handling, stopword filtering, OR matching with relevance scoring
	private function search_indexed_content($query) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_content_index';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return "No indexed content available.";
        }
        
		$raw_query = trim(strtolower($query));
		$terms = array();
		$phrases = array();
		
		// Extract quoted phrases
		if (preg_match_all('/"([^"]+)"|\'([^\']+)\'/u', $raw_query, $matches)) {
			foreach ($matches[1] as $m) { if (!empty($m)) { $phrases[] = trim($m); } }
			foreach ($matches[2] as $m) { if (!empty($m)) { $phrases[] = trim($m); } }
			$raw_query = preg_replace('/"([^"]+)"|\'([^\']+)\'/u', ' ', $raw_query);
		}
		
		// Tokenize remaining text
		$tokens = preg_split('/[^a-z0-9\-\+]+/u', $raw_query, -1, PREG_SPLIT_NO_EMPTY);
		
		// Common stopwords to ignore in matching
		$stop_words = array('a','about','above','after','again','against','all','am','an','and','any','are','as','at','be','because','been','before','being','below','between','both','but','by','did','do','does','doing','down','during','each','few','for','from','further','had','has','have','having','he','her','here','hers','herself','him','himself','his','how','i','if','in','into','is','it','its','itself','me','more','most','my','myself','no','nor','not','of','off','on','once','only','or','other','our','ours','ourselves','out','over','own','same','she','should','so','some','such','than','that','the','their','theirs','them','themselves','then','there','these','they','this','those','through','to','too','under','until','up','very','was','we','were','what','when','where','which','while','who','whom','why','with','you','your','yours','yourself','yourselves');
		
		// Add excluded keywords from admin settings
		$excluded_keywords = get_option('ai_chatbot_excluded_keywords', '');
		if (!empty($excluded_keywords)) {
			$excluded_array = array_map('trim', explode(',', $excluded_keywords));
			$excluded_array = array_filter($excluded_array); // Remove empty values
			$stop_words = array_merge($stop_words, $excluded_array);
		}
		
		foreach ($tokens as $t) {
			$t = trim($t);
			if ($t === '') { continue; }
			if (strlen($t) <= 2 && $t !== 'bi') { continue; }
			if (in_array($t, $stop_words, true)) { continue; }
			$terms[] = $t;
		}
		
		// If all terms were filtered out (all excluded keywords), return no content
		if (empty($terms) && empty($phrases)) {
			return "No relevant content found. (All search terms were excluded keywords)";
		}
		
		// Special handling for "power bi" phrase and variant "powerbi"
		$raw_no_space = preg_replace('/\s+/', ' ', trim(strtolower($query)));
		if (strpos($raw_no_space, 'power bi') !== false) {
			$phrases[] = 'power bi';
			$terms[] = 'powerbi';
			$terms[] = 'power';
			$terms[] = 'bi';
		}
		
		$conditions = array();
		$score_parts = array();
		
		// Phrase conditions (score higher)
		foreach ($phrases as $phrase) {
			$safe = $wpdb->esc_like($phrase);
			$conditions[] = $wpdb->prepare("(LOWER(keywords) LIKE %s OR LOWER(title) LIKE %s OR LOWER(tags) LIKE %s OR LOWER(content) LIKE %s)", '%' . $safe . '%', '%' . $safe . '%', '%' . $safe . '%', '%' . $safe . '%');
			$score_parts[] = $wpdb->prepare("(CASE WHEN LOWER(title) LIKE %s THEN 6 ELSE 0 END) + (CASE WHEN LOWER(keywords) LIKE %s THEN 6 ELSE 0 END) + (CASE WHEN LOWER(tags) LIKE %s THEN 5 ELSE 0 END) + (CASE WHEN LOWER(content) LIKE %s THEN 2 ELSE 0 END)", '%' . $safe . '%', '%' . $safe . '%', '%' . $safe . '%', '%' . $safe . '%');
		}
		
		// Term conditions (lower weight)
		foreach ($terms as $term) {
			$safe = $wpdb->esc_like($term);
			$conditions[] = $wpdb->prepare("(LOWER(keywords) LIKE %s OR LOWER(title) LIKE %s OR LOWER(tags) LIKE %s OR LOWER(content) LIKE %s)", '%' . $safe . '%', '%' . $safe . '%', '%' . $safe . '%', '%' . $safe . '%');
			$score_parts[] = $wpdb->prepare("(CASE WHEN LOWER(title) LIKE %s THEN 3 ELSE 0 END) + (CASE WHEN LOWER(keywords) LIKE %s THEN 4 ELSE 0 END) + (CASE WHEN LOWER(tags) LIKE %s THEN 3 ELSE 0 END) + (CASE WHEN LOWER(content) LIKE %s THEN 1 ELSE 0 END)", '%' . $safe . '%', '%' . $safe . '%', '%' . $safe . '%', '%' . $safe . '%');
		}
		
		$results = array();
		if (!empty($conditions)) {
			$where_clause = implode(' OR ', $conditions);
			$score_expr = implode(' + ', $score_parts);
			$sql = "SELECT title, url, content, (" . $score_expr . ") AS relevance_score FROM $table_name WHERE $where_clause ORDER BY relevance_score DESC, indexed_date DESC LIMIT 5";
			$results = $wpdb->get_results($sql);
		}
		
		// Fallback: broad search on the full query string
		if (empty($results)) {
			$sql = "SELECT title, url, content FROM $table_name WHERE LOWER(title) LIKE %s OR LOWER(keywords) LIKE %s OR LOWER(tags) LIKE %s OR LOWER(content) LIKE %s ORDER BY indexed_date DESC LIMIT 5";
			$like = '%' . $wpdb->esc_like(strtolower($query)) . '%';
			$results = $wpdb->get_results($wpdb->prepare($sql, $like, $like, $like, $like));
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
    
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'ai-chatbot') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        // Enqueue admin JS based on page
        if ($hook === 'toplevel_page_ai-chatbot') {
            wp_enqueue_script(
                'ai-chatbot-admin-dashboard',
                AI_CHATBOT_PLUGIN_URL . 'assets/js/admin/admin-dashboard.js',
                array('jquery'),
                AI_CHATBOT_VERSION,
                true
            );
        } elseif ($hook === 'ai-chatbot_page_ai-chatbot-models') {
            wp_enqueue_script(
                'ai-chatbot-model-selector',
                AI_CHATBOT_PLUGIN_URL . 'assets/js/admin/model-selector.js',
                array('jquery'),
                AI_CHATBOT_VERSION,
                true
            );
        } elseif ($hook === 'ai-chatbot_page_ai-chatbot-content') {
            wp_enqueue_script(
                'ai-chatbot-content-index',
                AI_CHATBOT_PLUGIN_URL . 'assets/js/admin/content-index.js',
                array('jquery'),
                AI_CHATBOT_VERSION,
                true
            );
        }
        
        // Localize admin data
        wp_localize_script('jquery', 'aiChatbotAdmin', array(
            'nonce' => wp_create_nonce('ai_chatbot_admin_nonce'),
            'currentModel' => get_option('ai_chatbot_model', ''),
            'saveModelUrl' => admin_url('admin.php?page=ai-chatbot-models&save_model=')
        ));
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
