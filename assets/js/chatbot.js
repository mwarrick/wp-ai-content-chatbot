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

		function addMessage(text, sender) {
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
				messageHtml = '<div class="chat-message ' + sender + '"><div class="message-bubble ' + sender + '">' + htmlContent + '</div></div>';
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
				if (response && response.success && response.data && response.data.response) {
					addMessage(response.data.response, 'bot');
				} else {
					addMessage('Sorry, I encountered an error. Please try again.', 'bot');
				}
			}).fail(function(){
				hideTyping();
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
	});
})(jQuery);
