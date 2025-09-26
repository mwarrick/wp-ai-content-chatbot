<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('ai_chatbot_api_key');
delete_option('ai_chatbot_model');
delete_option('ai_chatbot_system_prompt');
delete_option('ai_chatbot_post_types');
delete_option('ai_chatbot_excluded_post_types');
delete_option('ai_chatbot_excluded_keywords');
delete_option('ai_chatbot_training_examples');

// Drop database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_content_index");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_interactions");

// Clear options with safe update mode handling
$wpdb->query("SET SQL_SAFE_UPDATES = 0");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ai_chatbot_%'");
$wpdb->query("SET SQL_SAFE_UPDATES = 1");

// Clear any cached data
wp_cache_flush();
?>
