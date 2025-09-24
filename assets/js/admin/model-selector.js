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
                            const selected = model.id === window.aiChatbotAdmin.currentModel ? ' selected' : '';
                            html += '<option value="' + model.id + '"' + selected + '>' + model.id + '</option>';
                        });
                        html += '</select><br>';
                        html += '<button type="button" id="save-model" class="button button-primary" style="margin-top: 10px; padding: 10px 20px;">💾 Save Selected Model</button>';
                        
                        if (response.data.note) {
                            html += '<br><br><small><em>Note: ' + response.data.note + '</em></small>';
                        }
                        html += '<br><small>Total models: ' + response.data.models.length + '</small>';
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
                showResult('<strong>❌ Request failed:</strong><br>Status: ' + status + '<br>Error: ' + error + '<br>HTTP Code: ' + xhr.status, 'error');
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
        
        window.location.href = window.aiChatbotAdmin.saveModelUrl + encodeURIComponent(selectedModel);
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
                showResult('❌ <strong>Claude API: REQUEST FAILED</strong><br>Status: ' + status + '<br>Error: ' + error + '<br>HTTP Code: ' + xhr.status, 'error');
            }
        });
    });
});
