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
                
                <input type="submit" class="button" value="Filter">
                
                <?php if (!empty($filter_success) || !empty($filter_date)): ?>
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
        ?>
        <p>
            <strong>Total Interactions:</strong> <?php echo number_format($total_items); ?> | 
            <strong>Successful:</strong> <?php echo number_format($success_count); ?> | 
            <strong>Errors:</strong> <?php echo number_format($error_count); ?> | 
            <strong>Avg Response Time:</strong> <?php echo $avg_response_time ? round($avg_response_time) . 'ms' : 'N/A'; ?>
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
                            <button type="button" class="button button-small" onclick="showLogDetails(<?php echo $log->id; ?>)">
                                View Details
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
    // This would typically make an AJAX call to get full log details
    // For now, we'll show a placeholder
    document.getElementById('log-details-content').innerHTML = '<p>Loading details for log ID: ' + logId + '</p><p><em>Full details view would be implemented with AJAX call to get complete log data.</em></p>';
    document.getElementById('log-details-modal').style.display = 'block';
}

function closeLogDetails() {
    document.getElementById('log-details-modal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('log-details-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
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
