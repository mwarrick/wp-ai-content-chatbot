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
            showResult('❌ <strong>WordPress AJAX: FAILED</strong><br>Error: ' + status + ' - ' + error + '<br>HTTP Code: ' + xhr.status, 'error');
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
                showResult('❌ <strong>Claude API: REQUEST FAILED</strong><br>Status: ' + status + '<br>Error: ' + error + '<br>HTTP Code: ' + xhr.status, 'error');
            }
        });
    });
});
