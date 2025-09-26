<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get AI learning analytics
$analytics = $this->get_ai_learning_analytics();
$patterns = $analytics['patterns'];
$suggestions = $analytics['suggestions'];
$overall_helpfulness_rate = $analytics['overall_helpfulness_rate'];
$total_feedback_analyzed = $analytics['total_feedback_analyzed'];
?>

<div class="wrap">
    <h1>AI Learning Analytics</h1>
    
    <div class="notice notice-info">
        <p><strong>AI Learning System:</strong> This system analyzes user feedback to improve chatbot responses over time. It tracks patterns in successful vs. unsuccessful interactions and suggests improvements.</p>
    </div>

    <!-- Overall Statistics -->
    <div class="card" style="max-width: 100%; margin: 20px 0;">
        <h2>Overall Performance</h2>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; min-width: 200px;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;">Helpfulness Rate</h3>
                <div style="font-size: 24px; font-weight: bold; color: <?php echo $overall_helpfulness_rate >= 0.7 ? '#28a745' : ($overall_helpfulness_rate >= 0.5 ? '#ffc107' : '#dc3545'); ?>;">
                    <?php echo $overall_helpfulness_rate > 0 ? round($overall_helpfulness_rate * 100) . '%' : 'No data'; ?>
                </div>
                <small style="color: #666;">
                    <?php if ($overall_helpfulness_rate >= 0.7): ?>
                        Excellent performance
                    <?php elseif ($overall_helpfulness_rate >= 0.5): ?>
                        Good performance
                    <?php else: ?>
                        Needs improvement
                    <?php endif; ?>
                </small>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; min-width: 200px;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;">Feedback Analyzed</h3>
                <div style="font-size: 24px; font-weight: bold; color: #0073aa;">
                    <?php echo number_format($total_feedback_analyzed); ?>
                </div>
                <small style="color: #666;">Total interactions analyzed</small>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; min-width: 200px;">
                <h3 style="margin: 0 0 10px 0; color: #856404;">Learning Suggestions</h3>
                <div style="font-size: 24px; font-weight: bold; color: #856404;">
                    <?php echo count($suggestions); ?>
                </div>
                <small style="color: #666;">Available suggestions</small>
            </div>
        </div>
    </div>

    <!-- Query Type Patterns -->
    <?php if (!empty($patterns)): ?>
    <div class="card" style="max-width: 100%; margin: 20px 0;">
        <h2>Query Type Performance</h2>
        <p>Analysis of feedback patterns by query type to identify what works best for different kinds of questions.</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Query Type</th>
                    <th>Feedback Type</th>
                    <th>Count</th>
                    <th>Avg Response Length</th>
                    <th>Links Rate</th>
                    <th>Avg Response Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patterns as $pattern_key => $data): ?>
                    <?php 
                    $parts = explode('_', $pattern_key);
                    $query_type = $parts[0];
                    $feedback_type = $parts[1];
                    $avg_response_time = !empty($data['response_times']) ? array_sum($data['response_times']) / count($data['response_times']) : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo ucfirst($query_type); ?></strong></td>
                        <td>
                            <span style="color: <?php echo $feedback_type === 'positive' ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                <?php echo $feedback_type === 'positive' ? '👍 Positive' : '👎 Negative'; ?>
                            </span>
                        </td>
                        <td><?php echo number_format($data['count']); ?></td>
                        <td><?php echo number_format($data['avg_length']); ?> chars</td>
                        <td><?php echo round($data['has_links_rate'] * 100); ?>%</td>
                        <td><?php echo $avg_response_time > 0 ? round($avg_response_time) . 'ms' : 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Learning Suggestions -->
    <?php if (!empty($suggestions)): ?>
    <div class="card" style="max-width: 100%; margin: 20px 0;">
        <h2>AI Learning Suggestions</h2>
        <p>Based on feedback analysis, here are suggestions to improve chatbot performance:</p>
        
        <?php foreach (array_reverse($suggestions) as $suggestion): ?>
            <div style="background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 10px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <strong>Learning Suggestion</strong>
                    <small style="color: #666;"><?php echo date('M j, Y g:i A', strtotime($suggestion['timestamp'])); ?></small>
                </div>
                <p style="margin: 0 0 10px 0;"><?php echo esc_html($suggestion['suggestion']); ?></p>
                <div style="font-size: 12px; color: #666;">
                    <strong>Context:</strong> Helpfulness rate was <?php echo round($suggestion['helpfulness_rate'] * 100); ?>% 
                    when this suggestion was generated (analyzed <?php echo $suggestion['patterns_analyzed']; ?> patterns)
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card" style="max-width: 100%; margin: 20px 0;">
        <h2>Learning Suggestions</h2>
        <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;">
            <p style="margin: 0;"><strong>No suggestions yet.</strong> The AI learning system needs more feedback data to generate suggestions. Once you have at least 10 feedback interactions, the system will start analyzing patterns and providing improvement suggestions.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- How It Works -->
    <div class="card" style="max-width: 100%; margin: 20px 0;">
        <h2>How AI Learning Works</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="background: #f0f8ff; padding: 15px; border-radius: 5px;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;">📊 Pattern Analysis</h3>
                <p style="margin: 0;">Analyzes feedback patterns by query type (informational, capability, person, etc.) to identify what makes responses helpful or unhelpful.</p>
            </div>
            
            <div style="background: #f0fff0; padding: 15px; border-radius: 5px;">
                <h3 style="margin: 0 0 10px 0; color: #28a745;">🎯 Content Relevance</h3>
                <p style="margin: 0;">Boosts relevance scores for content that receives positive feedback and enhances keywords based on successful queries.</p>
            </div>
            
            <div style="background: #fff8f0; padding: 15px; border-radius: 5px;">
                <h3 style="margin: 0 0 10px 0; color: #fd7e14;">💡 Smart Suggestions</h3>
                <p style="margin: 0;">Generates actionable suggestions for improving system prompts and response quality based on feedback patterns.</p>
            </div>
            
            <div style="background: #f8f0ff; padding: 15px; border-radius: 5px;">
                <h3 style="margin: 0 0 10px 0; color: #6f42c1;">🔄 Continuous Learning</h3>
                <p style="margin: 0;">Continuously learns from user feedback to improve search relevance and response quality over time.</p>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card" style="max-width: 100%; margin: 20px 0;">
        <h2>Actions</h2>
        <p>
            <a href="<?php echo admin_url('admin.php?page=ai-chatbot-logs'); ?>" class="button button-primary">
                View Interaction Logs
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-chatbot-content'); ?>" class="button">
                Re-index Content
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-chatbot-settings'); ?>" class="button">
                Adjust Settings
            </a>
        </p>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
    color: #1d2327;
}

.wp-list-table th {
    background: #f6f7f7;
    font-weight: 600;
}

.wp-list-table td {
    vertical-align: middle;
}
</style>
