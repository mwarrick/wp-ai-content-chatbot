<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get training examples
$training_examples = get_option('ai_chatbot_training_examples', array());
?>

<div class="wrap">
    <h1>AI Training</h1>
    
    <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
        <h2>About AI Training</h2>
        <p>Train your AI chatbot by providing specific question and answer pairs. When users ask questions similar to your training examples, the AI will use your exact answers instead of searching through website content.</p>
        
        <h3>How it works:</h3>
        <ol>
            <li><strong>Add Training Examples:</strong> Create question/answer pairs for common queries</li>
            <li><strong>Smart Matching:</strong> The system finds similar questions using intelligent matching</li>
            <li><strong>Priority Response:</strong> Training examples take priority over content search</li>
            <li><strong>Fallback:</strong> If no training match is found, the AI searches your website content</li>
        </ol>
    </div>
    
    <!-- Add New Training Example Form -->
    <div style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h2>Add New Training Example</h2>
        <form id="add-training-form">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ai_training_nonce'); ?>">
            <input type="hidden" name="action" value="add_training_example">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="question">Question</label></th>
                    <td>
                        <input type="text" id="question" name="question" class="regular-text" placeholder="e.g., How can I contact you?" required>
                        <p class="description">The question users might ask</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="correct_answer">Correct Answer</label></th>
                    <td>
                        <textarea id="correct_answer" name="correct_answer" rows="4" cols="50" class="large-text" placeholder="e.g., You can contact me through the contact page at https://example.com/contact" required></textarea>
                        <p class="description">The exact answer you want the AI to give</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="category">Category</label></th>
                    <td>
                        <select id="category" name="category">
                            <option value="general">General</option>
                            <option value="contact">Contact</option>
                            <option value="services">Services</option>
                            <option value="pricing">Pricing</option>
                            <option value="support">Support</option>
                            <option value="about">About</option>
                        </select>
                        <p class="description">Category for organization (optional)</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="Add Training Example">
            </p>
        </form>
    </div>
    
    <!-- Existing Training Examples -->
    <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
        <h2>Training Examples (<?php echo count($training_examples); ?>)</h2>
        
        <?php if (empty($training_examples)): ?>
            <p>No training examples yet. Add some above to get started!</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 25%;">Question</th>
                        <th style="width: 45%;">Answer</th>
                        <th style="width: 10%;">Category</th>
                        <th style="width: 10%;">Created</th>
                        <th style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($training_examples as $example): ?>
                        <tr>
                            <td><strong><?php echo esc_html($example['question']); ?></strong></td>
                            <td><?php echo esc_html(substr($example['correct_answer'], 0, 100)) . (strlen($example['correct_answer']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo esc_html($example['category']); ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($example['created_at']))); ?></td>
                            <td>
                                <button class="button button-small delete-training-example" data-id="<?php echo esc_attr($example['id']); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Test Training System -->
    <div style="background: #fff3cd; padding: 20px; margin: 20px 0; border: 1px solid #ffeaa7; border-radius: 5px;">
        <h2>Test Training System</h2>
        <p>Test how the training system responds to a question:</p>
        
        <form id="test-training-form">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ai_training_nonce'); ?>">
            <input type="hidden" name="action" value="test_training_response">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="test_query">Test Question</label></th>
                    <td>
                        <input type="text" id="test_query" name="test_query" class="regular-text" placeholder="e.g., How do I contact you?">
                        <input type="submit" class="button" value="Test Response">
                    </td>
                </tr>
            </table>
        </form>
        
        <div id="test-results" style="margin-top: 20px; display: none;">
            <h3>Test Results:</h3>
            <div id="test-output"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Add training example
    $('#add-training-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert('Training example added successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Delete training example
    $('.delete-training-example').on('click', function() {
        if (!confirm('Are you sure you want to delete this training example?')) {
            return;
        }
        
        var exampleId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.post(ajaxurl, {
            action: 'delete_training_example',
            example_id: exampleId,
            nonce: '<?php echo wp_create_nonce('ai_training_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                $row.fadeOut(function() {
                    $row.remove();
                });
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Test training system
    $('#test-training-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var $results = $('#test-results');
        var $output = $('#test-output');
        
        $results.show();
        $output.html('<p>Testing...</p>');
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                var html = '<div style="background: #d4edda; padding: 10px; border-radius: 3px; margin: 10px 0;">';
                html += '<p><strong>Query:</strong> ' + escapeHtml(response.data.query) + '</p>';
                html += '<p><strong>Message:</strong> ' + escapeHtml(response.data.message) + '</p>';
                
                if (response.data.similar_examples && response.data.similar_examples.length > 0) {
                    html += '<h4>Similar Examples Found:</h4>';
                    response.data.similar_examples.forEach(function(similar, index) {
                        var score = Math.round(similar.similarity_score * 100);
                        html += '<div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 3px;">';
                        html += '<p><strong>Match ' + (index + 1) + ' (' + score + '% similar):</strong></p>';
                        html += '<p><strong>Q:</strong> ' + escapeHtml(similar.example.question) + '</p>';
                        html += '<p><strong>A:</strong> ' + escapeHtml(similar.example.correct_answer) + '</p>';
                        html += '</div>';
                    });
                    
                    if (response.data.best_match) {
                        html += '<div style="background: #d1ecf1; padding: 10px; border-radius: 3px; margin: 10px 0;">';
                        html += '<h4>Best Match (AI would use this response):</h4>';
                        html += '<p><strong>Q:</strong> ' + escapeHtml(response.data.best_match.example.question) + '</p>';
                        html += '<p><strong>A:</strong> ' + escapeHtml(response.data.best_match.example.correct_answer) + '</p>';
                        html += '</div>';
                    }
                }
                
                html += '</div>';
                $output.html(html);
            } else {
                $output.html('<div style="background: #f8d7da; padding: 10px; border-radius: 3px; color: #721c24;">Error: ' + response.data + '</div>');
            }
        });
    });
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>