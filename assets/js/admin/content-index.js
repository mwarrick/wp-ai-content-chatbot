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
                nonce: window.aiChatbotAdmin.nonce
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
                nonce: window.aiChatbotAdmin.nonce
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
