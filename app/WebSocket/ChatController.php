<?php

namespace App\WebSocket;

use Sockeon\Sockeon\Controllers\SocketController;
use Sockeon\Sockeon\WebSocket\Attributes\OnConnect;
use Sockeon\Sockeon\WebSocket\Attributes\OnDisconnect;
use Sockeon\Sockeon\WebSocket\Attributes\SocketOn;

class ChatController extends SocketController
{
    #[OnConnect]
    public function onConnect(int $clientId): void
    {
        echo "Client {$clientId} connected\n";
        
        $this->emit($clientId, 'welcome', [
            'message' => 'Welcome to the chat!',
            'clientId' => $clientId
        ]);
        
        $this->broadcast('user.connected', [
            'clientId' => $clientId,
            'message' => "User {$clientId} joined the chat"
        ]);
    }

    #[OnDisconnect]
    public function onDisconnect(int $clientId): void
    {
        echo "Client {$clientId} disconnected\n";
        
        $this->broadcast('user.disconnected', [
            'clientId' => $clientId,
            'message' => "User {$clientId} left the chat"
        ]);
    }

    #[SocketOn('chat.message')]
    public function handleMessage(int $clientId, array $data): void
    {
        $message = $data['message'] ?? '';
        
        if (empty($message)) {
            $this->emit($clientId, 'error', ['message' => 'Message cannot be empty']);
            return;
        }

        $this->broadcast('chat.message', [
            'from' => $clientId,
            'message' => $message,
            'timestamp' => time()
        ]);
    }

    #[SocketOn('user.typing')]
    public function handleTyping(int $clientId, array $data): void
    {
        $isTyping = $data['typing'] ?? false;
        
        $this->broadcast('user.typing', [
            'clientId' => $clientId,
            'typing' => $isTyping
        ]);
    }
}
