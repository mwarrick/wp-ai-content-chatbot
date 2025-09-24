(function($){
	$(document).ready(function(){
		console.log('AI Chatbot: JavaScript loaded successfully'); // Debug logging
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
					console.log('AI Chatbot: Adding feedback buttons for interaction ID:', interactionId); // Debug logging
					messageHtml += '<div class="feedback-buttons" style="margin-top: 8px; text-align: right; background: #f0f0f0; padding: 5px; border-radius: 3px;">';
					messageHtml += '<button class="feedback-btn helpful" data-interaction-id="' + interactionId + '" data-helpful="1" style="background: #28a745; border: 1px solid #28a745; color: white; cursor: pointer; font-size: 16px; margin-right: 10px; padding: 5px 10px; border-radius: 3px;" title="This was helpful">👍 Helpful</button>';
					messageHtml += '<button class="feedback-btn not-helpful" data-interaction-id="' + interactionId + '" data-helpful="0" style="background: #dc3545; border: 1px solid #dc3545; color: white; cursor: pointer; font-size: 16px; padding: 5px 10px; border-radius: 3px;" title="This was not helpful">👎 Not Helpful</button>';
					messageHtml += '</div>';
				} else {
					console.log('AI Chatbot: No interaction ID provided, skipping feedback buttons'); // Debug logging
				}
				messageHtml += '</div>';
			} else {
				messageHtml = '<div class="chat-message ' + sender + '"><div class="message-bubble ' + sender + '">' + escapeHtml(text) + '</div></div>';
			}
			$('#chat-messages').append(messageHtml);
			
			// Debug: Check if feedback buttons were created
			if (interactionId) {
				var $feedbackButtons = $('.feedback-btn[data-interaction-id="' + interactionId + '"]');
				console.log('AI Chatbot: Feedback buttons created:', $feedbackButtons.length); // Debug logging
				
				// Test if buttons are clickable
				$feedbackButtons.each(function(index) {
					var $btn = $(this);
					console.log('AI Chatbot: Button ' + index + ':', {
						text: $btn.text(),
						interactionId: $btn.data('interaction-id'),
						helpful: $btn.data('helpful'),
						visible: $btn.is(':visible'),
						enabled: !$btn.prop('disabled')
					});
				});
			}
			
			scrollToBottom();
		}

		function sendMessage() {
			console.log('AI Chatbot: sendMessage called'); // Debug logging
			if (isProcessing) return;
			var message = $('#chat-input').val().trim();
			if (!message) return;

			console.log('AI Chatbot: Sending message:', message); // Debug logging
			addMessage(message, 'user');
			$('#chat-input').val('');
			showTyping();
			isProcessing = true;
			$('#send-button').prop('disabled', true);

			var ajaxUrl = (window.AIChatbot && AIChatbot.ajaxUrl) ? AIChatbot.ajaxUrl : window.ajaxurl;
			var nonce = (window.AIChatbot && AIChatbot.nonce) ? AIChatbot.nonce : '';
			
			console.log('AI Chatbot: AJAX URL:', ajaxUrl); // Debug logging
			console.log('AI Chatbot: Nonce:', nonce); // Debug logging
			console.log('AI Chatbot: AIChatbot object:', window.AIChatbot); // Debug logging
			
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'chatbot_query',
					message: message,
					nonce: nonce
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

		// Test event delegation
		$(document).on('click', '*', function(e) {
			if ($(this).hasClass('feedback-btn')) {
				console.log('AI Chatbot: Any click on feedback button detected'); // Debug logging
			}
		});

		// Feedback button handlers
		$(document).on('click', '.feedback-btn', function(e){
			console.log('AI Chatbot: Feedback button clicked'); // Debug logging
			e.preventDefault();
			var $btn = $(this);
			var interactionId = $btn.data('interaction-id');
			var helpful = $btn.data('helpful');
			
			console.log('AI Chatbot: Feedback button data:', {interactionId: interactionId, helpful: helpful}); // Debug logging
			
			if (!interactionId) {
				console.log('AI Chatbot: No interaction ID found, ignoring click'); // Debug logging
				return;
			}
			
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
