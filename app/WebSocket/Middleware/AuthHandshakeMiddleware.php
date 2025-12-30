<?php

namespace App\WebSocket\Middleware;

use Sockeon\Sockeon\Connection\Server;
use Sockeon\Sockeon\Contracts\WebSocket\HandshakeMiddleware;
use Sockeon\Sockeon\WebSocket\HandshakeRequest;

class AuthHandshakeMiddleware implements HandshakeMiddleware
{
    public function handle(int $clientId, HandshakeRequest $request, callable $next, Server $server): bool|array
    {
        // Extract Authorization header
        $authHeader = $request->getHeader('Authorization');

        if ($authHeader) {
            // Remove 'Bearer ' prefix if present
            $token = str_starts_with($authHeader, 'Bearer ')
                ? substr($authHeader, 7)
                : $authHeader;

            // Store token for later use in the controller
            $server->setClientData($clientId, 'auth_token', $token);
        }

        // Continue with the handshake (we'll validate in the controller)
        return $next($clientId, $request);
    }
}
