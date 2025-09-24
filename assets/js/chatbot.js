(function($){
	$(document).ready(function(){
		var chatOpen = false;
		var isProcessing = false;

		function scrollToBottom() {
			var messagesContainer = $('#chat-messages')[0];
			if (messagesContainer) {
				messagesContainer.scrollTop = messagesContainer.scrollHeight;
			}
		}

		function escapeHtml(text) {
			var div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

		function showTyping() {
			$('#typing-indicator').show();
			scrollToBottom();
		}

		function hideTyping() {
			$('#typing-indicator').hide();
		}

		function addMessage(text, sender, interactionId) {
			var messageHtml = '';
			if (sender === 'bot') {
				var markdownLinkRegex = /\[([^\]]+)\]\(([^)]+)\)/g;
				var parts = text.split(markdownLinkRegex);
				var htmlContent = '';
				for (var i = 0; i < parts.length; i++) {
					if (i % 3 === 1) {
						var linkText = parts[i];
						var linkUrl = parts[i + 1];
						htmlContent += '<a href="' + linkUrl + '" target="_blank" style="text-decoration: underline; color: inherit;">' + escapeHtml(linkText) + '</a>';
						i++;
					} else {
						htmlContent += escapeHtml(parts[i]);
					}
				}
				messageHtml = '<div class="chat-message ' + sender + '" data-interaction-id="' + (interactionId || '') + '">';
				messageHtml += '<div class="message-bubble ' + sender + '">' + htmlContent + '</div>';
				if (interactionId) {
					messageHtml += '<div class="feedback-buttons" style="margin-top: 8px; text-align: right;">';
					messageHtml += '<button class="feedback-btn helpful" data-interaction-id="' + interactionId + '" data-helpful="1" style="background: none; border: none; color: #28a745; cursor: pointer; font-size: 16px; margin-right: 10px;" title="This was helpful">👍</button>';
					messageHtml += '<button class="feedback-btn not-helpful" data-interaction-id="' + interactionId + '" data-helpful="0" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 16px;" title="This was not helpful">👎</button>';
					messageHtml += '</div>';
				}
				messageHtml += '</div>';
			} else {
				messageHtml = '<div class="chat-message ' + sender + '"><div class="message-bubble ' + sender + '">' + escapeHtml(text) + '</div></div>';
			}
			$('#chat-messages').append(messageHtml);
			scrollToBottom();
		}

		function sendMessage() {
			if (isProcessing) return;
			var message = $('#chat-input').val().trim();
			if (!message) return;

			addMessage(message, 'user');
			$('#chat-input').val('');
			showTyping();
			isProcessing = true;
			$('#send-button').prop('disabled', true);

			$.ajax({
				url: (window.AIChatbot && AIChatbot.ajaxUrl) ? AIChatbot.ajaxUrl : window.ajaxurl,
				type: 'POST',
				data: {
					action: 'chatbot_query',
					message: message,
					nonce: (window.AIChatbot && AIChatbot.nonce) ? AIChatbot.nonce : ''
				}
			}).done(function(response){
				hideTyping();
				console.log('Chatbot response:', response); // Debug logging
				if (response && response.success && response.data && response.data.response) {
					console.log('Interaction ID:', response.data.interaction_id); // Debug logging
					addMessage(response.data.response, 'bot', response.data.interaction_id);
				} else {
					console.log('Error in response:', response); // Debug logging
					addMessage('Sorry, I encountered an error. Please try again.', 'bot');
				}
			}).fail(function(xhr, status, error){
				hideTyping();
				console.log('AJAX failed:', status, error, xhr.responseText); // Debug logging
				addMessage("Sorry, I'm having trouble connecting. Please try again later.", 'bot');
			}).always(function(){
				isProcessing = false;
				$('#send-button').prop('disabled', false);
				$('#chat-input').focus();
			});
		}

		$('#ai-chatbot-toggle, #close-chat').on('click', function(){
			chatOpen = !chatOpen;
			$('#ai-chatbot-container').toggle();
			if (chatOpen) {
				$('#chat-input').focus();
			}
		});

		$('#send-button').on('click', sendMessage);
		$('#chat-input').on('keypress', function(e){
			if (e.which === 13 && !e.shiftKey) {
				e.preventDefault();
				sendMessage();
			}
		});

		$('#ai-chatbot-container').on('click', function(e){
			e.stopPropagation();
		});

		$(document).on('click', function(e){
			if (chatOpen && !$(e.target).closest('#ai-chatbot-widget').length) {
				chatOpen = false;
				$('#ai-chatbot-container').hide();
			}
		});

		// Feedback button handlers
		$(document).on('click', '.feedback-btn', function(e){
			e.preventDefault();
			var $btn = $(this);
			var interactionId = $btn.data('interaction-id');
			var helpful = $btn.data('helpful');
			
			if (!interactionId) return;
			
			// Disable all feedback buttons for this message
			$btn.closest('.chat-message').find('.feedback-btn').prop('disabled', true);
			
			// Submit feedback
			console.log('Submitting feedback:', {interaction_id: interactionId, helpful: helpful}); // Debug logging
			$.post((window.AIChatbot && AIChatbot.ajaxUrl) ? AIChatbot.ajaxUrl : window.ajaxurl, {
				action: 'submit_feedback',
				interaction_id: interactionId,
				helpful: helpful,
				nonce: (window.AIChatbot && AIChatbot.nonce) ? AIChatbot.nonce : ''
			}).done(function(response){
				console.log('Feedback response:', response); // Debug logging
				if (response && response.success) {
					// Show feedback confirmation
					var $feedbackButtons = $btn.closest('.feedback-buttons');
					$feedbackButtons.html('<span style="color: #28a745; font-size: 12px;">✓ Thank you for your feedback!</span>');
				} else {
					console.log('Feedback submission failed:', response); // Debug logging
					// Re-enable buttons on error
					$btn.closest('.chat-message').find('.feedback-btn').prop('disabled', false);
				}
			}).fail(function(xhr, status, error){
				console.log('Feedback AJAX failed:', status, error, xhr.responseText); // Debug logging
				// Re-enable buttons on error
				$btn.closest('.chat-message').find('.feedback-btn').prop('disabled', false);
			});
		});
	});
})(jQuery);
