<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sockeon Chat - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .chat-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 800px;
            height: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .connection-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ef4444;
            animation: pulse 2s infinite;
        }

        .status-indicator.connected {
            background: #10b981;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9fafb;
        }

        .message {
            margin-bottom: 16px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.system {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            font-style: italic;
        }

        .message.chat {
            background: white;
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .message.chat.own {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
            max-width: 70%;
        }

        .message.chat.other {
            background: white;
            max-width: 70%;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 12px;
            opacity: 0.8;
        }

        .message-sender {
            font-weight: 600;
        }

        .message-time {
            font-size: 11px;
        }

        .message-content {
            font-size: 15px;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .typing-indicator {
            color: #6b7280;
            font-size: 14px;
            font-style: italic;
            padding: 8px 20px;
            display: none;
        }

        .typing-indicator.show {
            display: block;
        }

        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .input-wrapper {
            display: flex;
            gap: 12px;
        }

        #messageInput {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 24px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s;
        }

        #messageInput:focus {
            border-color: #667eea;
        }

        #sendButton {
            padding: 12px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 24px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        #sendButton:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        #sendButton:active {
            transform: translateY(0);
        }

        #sendButton:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        /* Scrollbar styling */
        .messages-container::-webkit-scrollbar {
            width: 8px;
        }

        .messages-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .messages-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h1>💬 Sockeon Chat</h1>
            <div class="connection-status">
                <div class="status-indicator" id="statusIndicator"></div>
                <span id="statusText">Disconnected</span>
            </div>
        </div>

        <div class="messages-container" id="messagesContainer">
            <!-- Messages will appear here -->
        </div>

        <div class="typing-indicator" id="typingIndicator">
            Someone is typing...
        </div>

        <div class="chat-input-container">
            <div class="input-wrapper">
                <input 
                    type="text" 
                    id="messageInput" 
                    placeholder="Type your message..." 
                    disabled
                >
                <button id="sendButton" disabled>Send</button>
            </div>
        </div>
    </div>

    <script>
        let ws = null;
        let clientId = null;
        let typingTimeout = null;

        const messagesContainer = document.getElementById('messagesContainer');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');
        const typingIndicator = document.getElementById('typingIndicator');

        function connect() {
            @if(config('app.env') === 'production')
                const wsUrl = 'wss://sockeon.pregnazone.com/sockeon';
            @else
                const wsUrl = 'ws://{{ config('sockeon.host') === '0.0.0.0' ? 'localhost' : config('sockeon.host') }}:{{ config('sockeon.port') }}';
            @endif
            
            console.log('Connecting to:', wsUrl);
            ws = new WebSocket(wsUrl);

            ws.onopen = () => {
                console.log('Connected to WebSocket server');
                updateConnectionStatus(true);
                messageInput.disabled = false;
                sendButton.disabled = false;
            };

            ws.onclose = () => {
                console.log('Disconnected from WebSocket server');
                updateConnectionStatus(false);
                messageInput.disabled = true;
                sendButton.disabled = true;
                
                setTimeout(connect, 3000);
            };

            ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                addSystemMessage('Connection error. Retrying...');
            };

            ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    handleMessage(data);
                } catch (error) {
                    console.error('Error parsing message:', error);
                }
            };
        }

        function updateConnectionStatus(connected) {
            if (connected) {
                statusIndicator.classList.add('connected');
                statusText.textContent = 'Connected';
            } else {
                statusIndicator.classList.remove('connected');
                statusText.textContent = 'Disconnected';
            }
        }

        function handleMessage(data) {
            console.log('Received:', data);

            switch (data.event) {
                case 'welcome':
                    clientId = data.data.clientId;
                    addSystemMessage(data.data.message + ` (Your ID: ${clientId})`);
                    break;

                case 'user.connected':
                    if (data.data.clientId !== clientId) {
                        addSystemMessage(data.data.message);
                    }
                    break;

                case 'user.disconnected':
                    addSystemMessage(data.data.message);
                    break;

                case 'chat.message':
                    addChatMessage(
                        data.data.from,
                        data.data.message,
                        data.data.timestamp
                    );
                    break;

                case 'user.typing':
                    if (data.data.clientId !== clientId) {
                        showTypingIndicator(data.data.typing);
                    }
                    break;

                case 'error':
                    addErrorMessage(data.data.message);
                    break;
            }
        }

        function addSystemMessage(message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message system';
            messageDiv.textContent = message;
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function addChatMessage(from, message, timestamp) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message chat ${from === clientId ? 'own' : 'other'}`;
            
            const time = new Date(timestamp * 1000).toLocaleTimeString();
            
            messageDiv.innerHTML = `
                <div class="message-header">
                    <span class="message-sender">User ${from}</span>
                    <span class="message-time">${time}</span>
                </div>
                <div class="message-content">${escapeHtml(message)}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function addErrorMessage(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            messagesContainer.appendChild(errorDiv);
            scrollToBottom();
            
            // Remove error message after 3 seconds
            setTimeout(() => errorDiv.remove(), 3000);
        }

        function showTypingIndicator(isTyping) {
            if (isTyping) {
                typingIndicator.classList.add('show');
            } else {
                typingIndicator.classList.remove('show');
            }
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            
            if (!message || !ws || ws.readyState !== WebSocket.OPEN) {
                return;
            }

            ws.send(JSON.stringify({
                event: 'chat.message',
                data: { message }
            }));

            messageInput.value = '';
            sendTypingStatus(false);
        }

        function sendTypingStatus(isTyping) {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                return;
            }

            ws.send(JSON.stringify({
                event: 'user.typing',
                data: { typing: isTyping }
            }));
        }

        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Event listeners
        sendButton.addEventListener('click', sendMessage);

        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        messageInput.addEventListener('input', () => {
            clearTimeout(typingTimeout);
            sendTypingStatus(true);
            
            typingTimeout = setTimeout(() => {
                sendTypingStatus(false);
            }, 1000);
        });

        // Connect on page load
        connect();
    </script>
</body>
</html>
