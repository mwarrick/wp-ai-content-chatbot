<?php
/**
 * Interaction Logs page template
 */

// Handle clear logs action
if (isset($_POST['clear_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_chatbot_logs')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_interactions';
    $result = $wpdb->query("TRUNCATE TABLE $table_name");
    
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>Interaction logs cleared successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Failed to clear logs.</p></div>';
    }
}

// Get pagination parameters
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$filter_success = isset($_GET['filter_success']) ? $_GET['filter_success'] : '';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_feedback = isset($_GET['filter_feedback']) ? $_GET['filter_feedback'] : '';

// Build query
global $wpdb;
$table_name = $wpdb->prefix . 'chatbot_interactions';

$where_conditions = array();
$where_values = array();

if ($filter_success !== '') {
    $where_conditions[] = 'success = %d';
    $where_values[] = intval($filter_success);
}

if (!empty($filter_date)) {
    $where_conditions[] = 'DATE(timestamp) = %s';
    $where_values[] = $filter_date;
}

if ($filter_feedback !== '') {
    if ($filter_feedback === 'with_feedback') {
        $where_conditions[] = 'feedback_helpful IS NOT NULL';
    } elseif ($filter_feedback === 'helpful') {
        $where_conditions[] = 'feedback_helpful = 1';
    } elseif ($filter_feedback === 'not_helpful') {
        $where_conditions[] = 'feedback_helpful = 0';
    } elseif ($filter_feedback === 'no_feedback') {
        $where_conditions[] = 'feedback_helpful IS NULL';
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM $table_name $where_clause";
if (!empty($where_values)) {
    $count_sql = $wpdb->prepare($count_sql, $where_values);
}
$total_items = $wpdb->get_var($count_sql);

// Get logs with pagination
$sql = "SELECT * FROM $table_name $where_clause ORDER BY timestamp DESC LIMIT %d OFFSET %d";
$where_values[] = $per_page;
$where_values[] = $offset;
$sql = $wpdb->prepare($sql, $where_values);
$logs = $wpdb->get_results($sql);

// Calculate pagination
$total_pages = ceil($total_items / $per_page);
?>

<div class="wrap">
    <h1>Chatbot Interaction Logs</h1>
    <p>View and analyze chatbot interactions, including user queries, AI responses, and any errors.</p>
    
    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="ai-chatbot-logs">
            
            <div class="alignleft actions">
                <select name="filter_success">
                    <option value="">All Results</option>
                    <option value="1" <?php selected($filter_success, '1'); ?>>Successful</option>
                    <option value="0" <?php selected($filter_success, '0'); ?>>Errors Only</option>
                </select>
                
                <input type="date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>" placeholder="Filter by date">
                
                <select name="filter_feedback">
                    <option value="">All Feedback</option>
                    <option value="with_feedback" <?php selected($filter_feedback, 'with_feedback'); ?>>With Feedback</option>
                    <option value="helpful" <?php selected($filter_feedback, 'helpful'); ?>>Helpful Only</option>
                    <option value="not_helpful" <?php selected($filter_feedback, 'not_helpful'); ?>>Not Helpful Only</option>
                    <option value="no_feedback" <?php selected($filter_feedback, 'no_feedback'); ?>>No Feedback</option>
                </select>
                
                <input type="submit" class="button" value="Filter">
                
                <?php if (!empty($filter_success) || !empty($filter_date) || !empty($filter_feedback)): ?>
                    <a href="<?php echo admin_url('admin.php?page=ai-chatbot-logs'); ?>" class="button">Clear Filters</a>
                <?php endif; ?>
            </div>
        </form>
        
        <div class="alignright actions">
            <form method="post" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all interaction logs? This cannot be undone.');">
                <?php wp_nonce_field('clear_chatbot_logs'); ?>
                <input type="submit" name="clear_logs" class="button button-secondary" value="Clear All Logs">
            </form>
        </div>
    </div>
    
    <!-- Stats Summary -->
    <div style="background: #f0f8ff; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa;">
        <h3>📊 Quick Stats</h3>
        <?php
        $success_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE success = 1");
        $error_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE success = 0");
        $avg_response_time = $wpdb->get_var("SELECT AVG(response_time_ms) FROM $table_name WHERE success = 1 AND response_time_ms > 0");
        $feedback_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE feedback_helpful IS NOT NULL");
        $helpful_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE feedback_helpful = 1");
        $not_helpful_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE feedback_helpful = 0");
        ?>
        <p>
            <strong>Total Interactions:</strong> <?php echo number_format($total_items); ?> | 
            <strong>Successful:</strong> <?php echo number_format($success_count); ?> | 
            <strong>Errors:</strong> <?php echo number_format($error_count); ?> | 
            <strong>Avg Response Time:</strong> <?php echo $avg_response_time ? round($avg_response_time) . 'ms' : 'N/A'; ?>
        </p>
        <p>
            <strong>Feedback Received:</strong> <?php echo number_format($feedback_count); ?> | 
            <strong>Helpful:</strong> <?php echo number_format($helpful_count); ?> | 
            <strong>Not Helpful:</strong> <?php echo number_format($not_helpful_count); ?> | 
            <strong>Helpfulness Rate:</strong> <?php echo $feedback_count > 0 ? round(($helpful_count / $feedback_count) * 100) . '%' : 'N/A'; ?>
        </p>
    </div>
    
    <?php if (empty($logs)): ?>
        <div class="notice notice-info">
            <p>No interaction logs found. Try the chatbot on your website to generate some logs!</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 120px;">Timestamp</th>
                    <th style="width: 60px;">Status</th>
                    <th style="width: 80px;">Response Time</th>
                    <th>User Query</th>
                    <th>AI Response</th>
                    <th style="width: 80px;">Feedback</th>
                    <th style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log->id; ?></td>
                        <td>
                            <?php echo date('M j, Y', strtotime($log->timestamp)); ?><br>
                            <small><?php echo date('g:i:s A', strtotime($log->timestamp)); ?></small>
                        </td>
                        <td>
                            <?php if ($log->success): ?>
                                <span style="color: green; font-weight: bold;">✓ Success</span>
                            <?php else: ?>
                                <span style="color: red; font-weight: bold;">✗ Error</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->response_time_ms > 0): ?>
                                <?php echo number_format($log->response_time_ms); ?>ms
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo esc_html($log->user_query); ?>
                            </div>
                        </td>
                        <td>
                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo esc_html(substr($log->ai_response, 0, 100)); ?>
                                <?php if (strlen($log->ai_response) > 100): ?>...<?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($log->feedback_helpful === '1'): ?>
                                <span style="color: #28a745; font-weight: bold;">👍 Helpful</span>
                            <?php elseif ($log->feedback_helpful === '0'): ?>
                                <span style="color: #dc3545; font-weight: bold;">👎 Not Helpful</span>
                            <?php else: ?>
                                <span style="color: #6c757d;">No feedback</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small" onclick="showLogDetails(<?php echo $log->id; ?>)">
                                View Details
                            </button>
                            <br><br>
                            <button type="button" class="button button-small button-secondary" onclick="createTrainingExample(<?php echo $log->id; ?>)" style="background: #0073aa; color: white; border-color: #0073aa;">
                                Create Training
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo number_format($total_items); ?> items</span>
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $page
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal for log details -->
<div id="log-details-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; max-height: 80%; overflow-y: auto;">
        <span style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;" onclick="closeLogDetails()">&times;</span>
        <h2>Interaction Details</h2>
        <div id="log-details-content"></div>
    </div>
</div>

<script>
function showLogDetails(logId) {
    document.getElementById('log-details-content').innerHTML = '<p><em>Loading details...</em></p>';
    document.getElementById('log-details-modal').style.display = 'block';
    
    // Make AJAX call to get log details
    jQuery.post(ajaxurl, {
        action: 'get_log_details',
        log_id: logId,
        nonce: '<?php echo wp_create_nonce('ai_chatbot_admin_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            var log = response.data;
            var html = '<div style="margin-bottom: 20px;">';
            
            // Basic info
            html += '<h3>Basic Information</h3>';
            html += '<table class="widefat" style="margin-bottom: 20px;">';
            html += '<tr><td><strong>ID:</strong></td><td>' + log.id + '</td></tr>';
            html += '<tr><td><strong>Timestamp:</strong></td><td>' + log.timestamp + '</td></tr>';
            html += '<tr><td><strong>Status:</strong></td><td>' + (log.success == 1 ? '<span style="color: green;">✓ Success</span>' : '<span style="color: red;">✗ Error</span>') + '</td></tr>';
            html += '<tr><td><strong>Response Time:</strong></td><td>' + (log.response_time_ms > 0 ? log.response_time_ms + 'ms' : 'N/A') + '</td></tr>';
            html += '<tr><td><strong>API Model:</strong></td><td>' + (log.api_model || 'N/A') + '</td></tr>';
            html += '<tr><td><strong>User IP:</strong></td><td>' + (log.user_ip || 'N/A') + '</td></tr>';
            html += '</table>';
            
            // User query
            html += '<h3>User Query</h3>';
            html += '<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-bottom: 20px;">';
            html += '<pre style="white-space: pre-wrap; margin: 0;">' + escapeHtml(log.user_query || 'N/A') + '</pre>';
            html += '</div>';
            
            // AI response
            html += '<h3>AI Response</h3>';
            html += '<div style="background: #f0f8ff; padding: 15px; border-left: 4px solid #28a745; margin-bottom: 20px;">';
            html += '<pre style="white-space: pre-wrap; margin: 0;">' + escapeHtml(log.ai_response || 'N/A') + '</pre>';
            html += '</div>';
            
            // Relevant content
            if (log.relevant_content && log.relevant_content.trim() !== '') {
                html += '<h3>Relevant Content Found</h3>';
                html += '<div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-bottom: 20px; max-height: 300px; overflow-y: auto;">';
                html += '<pre style="white-space: pre-wrap; margin: 0; font-size: 12px;">' + escapeHtml(log.relevant_content) + '</pre>';
                html += '</div>';
            }
            
            // Error message
            if (log.error_message && log.error_message.trim() !== '') {
                html += '<h3>Error Details</h3>';
                html += '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin-bottom: 20px;">';
                html += '<pre style="white-space: pre-wrap; margin: 0; color: #721c24;">' + escapeHtml(log.error_message) + '</pre>';
                html += '</div>';
            }
            
            // Feedback information
            if (log.feedback_helpful !== null) {
                html += '<h3>User Feedback</h3>';
                html += '<div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px;">';
                html += '<p><strong>Rating:</strong> ' + (log.feedback_helpful == 1 ? '👍 Helpful' : '👎 Not Helpful') + '</p>';
                if (log.feedback_rating) {
                    html += '<p><strong>Star Rating:</strong> ' + log.feedback_rating + '/5</p>';
                }
                if (log.feedback_comment && log.feedback_comment.trim() !== '') {
                    html += '<p><strong>Comment:</strong></p>';
                    html += '<p style="font-style: italic;">' + escapeHtml(log.feedback_comment) + '</p>';
                }
                if (log.feedback_timestamp) {
                    html += '<p><strong>Feedback Date:</strong> ' + log.feedback_timestamp + '</p>';
                }
                html += '</div>';
            }
            
            // User agent
            if (log.user_agent && log.user_agent.trim() !== '') {
                html += '<h3>User Agent</h3>';
                html += '<div style="background: #e2e3e5; padding: 15px; border-left: 4px solid #6c757d; margin-bottom: 20px;">';
                html += '<pre style="white-space: pre-wrap; margin: 0; font-size: 11px;">' + escapeHtml(log.user_agent) + '</pre>';
                html += '</div>';
            }
            
            html += '</div>';
            document.getElementById('log-details-content').innerHTML = html;
        } else {
            document.getElementById('log-details-content').innerHTML = '<p style="color: red;">Error loading log details: ' + (response.data || 'Unknown error') + '</p>';
        }
    }).fail(function() {
        document.getElementById('log-details-content').innerHTML = '<p style="color: red;">Failed to load log details. Please try again.</p>';
    });
}

function closeLogDetails() {
    document.getElementById('log-details-modal').style.display = 'none';
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Create training example from interaction log
function createTrainingExample(logId) {
    // Get log details first
    jQuery.post(ajaxurl, {
        action: 'get_log_details',
        log_id: logId,
        nonce: '<?php echo wp_create_nonce('ai_chatbot_admin_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            var log = response.data;
            
            // Create a form to pre-fill training data
            var formHtml = '<div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">';
            formHtml += '<h3>Create Training Example from Interaction</h3>';
            formHtml += '<form id="quick-training-form">';
            formHtml += '<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ai_training_nonce'); ?>">';
            formHtml += '<input type="hidden" name="action" value="add_training_example">';
            
            formHtml += '<table class="form-table">';
            formHtml += '<tr>';
            formHtml += '<th scope="row"><label>Question</label></th>';
            formHtml += '<td><input type="text" name="question" value="' + escapeHtml(log.user_query) + '" class="regular-text" readonly style="background: #f0f0f0;"></td>';
            formHtml += '</tr>';
            
            formHtml += '<tr>';
            formHtml += '<th scope="row"><label>Correct Answer</label></th>';
            formHtml += '<td><textarea name="correct_answer" rows="4" cols="50" class="regular-text">' + escapeHtml(log.ai_response) + '</textarea>';
            formHtml += '<p class="description">Edit the AI response to make it the ideal answer for this question.</p></td>';
            formHtml += '</tr>';
            
            formHtml += '<tr>';
            formHtml += '<th scope="row"><label>Category</label></th>';
            formHtml += '<td><select name="category">';
            formHtml += '<option value="general">General</option>';
            formHtml += '<option value="contact">Contact</option>';
            formHtml += '<option value="services">Services</option>';
            formHtml += '<option value="pricing">Pricing</option>';
            formHtml += '<option value="support">Support</option>';
            formHtml += '<option value="about">About</option>';
            formHtml += '</select></td>';
            formHtml += '</tr>';
            formHtml += '</table>';
            
            formHtml += '<p class="submit">';
            formHtml += '<input type="submit" class="button button-primary" value="Add Training Example">';
            formHtml += '<button type="button" class="button" onclick="closeTrainingForm()" style="margin-left: 10px;">Cancel</button>';
            formHtml += '</p>';
            
            formHtml += '</form>';
            formHtml += '</div>';
            
            // Show the form in a modal or inline
            var modal = document.createElement('div');
            modal.id = 'training-modal';
            modal.style.cssText = 'position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);';
            modal.innerHTML = '<div style="background-color: white; margin: 5% auto; padding: 20px; border-radius: 5px; width: 80%; max-width: 800px; max-height: 80%; overflow-y: auto;">' + formHtml + '</div>';
            
            document.body.appendChild(modal);
            
            // Handle form submission
            jQuery('#quick-training-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = jQuery(this).serialize();
                
                jQuery.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        alert('Training example added successfully!');
                        document.body.removeChild(modal);
                        // Optionally redirect to training page
                        window.open('<?php echo admin_url('admin.php?page=ai-chatbot-training'); ?>', '_blank');
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
        } else {
            alert('Error loading log details: ' + response.data);
        }
    });
}

function closeTrainingForm() {
    var modal = document.getElementById('training-modal');
    if (modal) {
        document.body.removeChild(modal);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('log-details-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
    
    var trainingModal = document.getElementById('training-modal');
    if (event.target == trainingModal) {
        document.body.removeChild(trainingModal);
    }
}
</script>

<style>
.wp-list-table th, .wp-list-table td {
    vertical-align: top;
}

.wp-list-table td {
    padding: 8px;
}

.tablenav {
    margin: 6px 0 4px;
    padding: 0;
}

.tablenav .actions {
    padding: 2px 8px 0 0;
}

.tablenav .actions select, .tablenav .actions input[type="date"] {
    margin-right: 5px;
}
</style>
