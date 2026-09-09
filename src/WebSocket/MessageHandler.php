<?php
declare(strict_types=1);

// workers/MessageHandler.php

namespace Symphograph\Bicycle\WebSocket;

use Workerman\Connection\TcpConnection;

class MessageHandler
{
    public function __construct(
        private ConnectionManager $connectionManager,
        private Authenticator     $authenticator
    )
    {
    }

    public function handle(TcpConnection $conn, string $msg): void
    {
        $sConn = $this->connectionManager->getConnection($conn->id);
        if ($sConn === null) {
            SocketConnect::log("Unknown connection: {$conn->id}");
            return;
        }

        $data = json_decode($msg, true);
        if (!is_array($data)) {
            SocketConnect::log("Invalid JSON from client {$conn->id}");
            $conn->close();
            $this->connectionManager->removeConnection($conn->id);
            return;
        }

        if (!$sConn->isAuthenticated()) {
            $this->handleUnauthenticatedMessage($conn, $sConn, $data);
            return;
        }

        $this->handleAuthenticatedMessage($sConn, $data);
    }

    private function handleUnauthenticatedMessage(
        TcpConnection $conn,
        SocketConnect $sConn,
        array         $data
    ): void
    {
        if (($data['type'] ?? '') === 'auth') {
            $this->authenticator->authenticate($conn, $data['token'] ?? '');
            return;
        }

        SocketConnect::log("Non-auth message from unauthenticated client {$conn->id}, closing");
        $conn->close();
        $this->connectionManager->removeConnection($conn->id);
    }

    private function handleAuthenticatedMessage(SocketConnect $sConn, array $data): void
    {
        switch ($data['type'] ?? '') {
            case 'pong':
                $sConn->updatePong();
                break;
            default:
                SocketConnect::log(
                    "Unknown message type from client {$sConn->id()}: " . ($data['type'] ?? 'unknown')
                );
        }
    }
}