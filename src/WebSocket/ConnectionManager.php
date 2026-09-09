<?php

namespace Symphograph\Bicycle\WebSocket;

use Workerman\Connection\TcpConnection;

class ConnectionManager
{
    /** @var array<int, SocketConnect> */
    private array $connections = [];

    public function addConnection(TcpConnection $conn): void
    {
        $this->connections[$conn->id] = new SocketConnect($conn);
    }

    public function removeConnection(int $id): void
    {
        unset($this->connections[$id]);
    }

    public function getConnection(int $id): ?SocketConnect
    {
        return $this->connections[$id] ?? null;
    }

    /**
     * Возвращает все активные соединения (включая неаутентифицированные).
     *
     * @return array<int, SocketConnect>
     */
    public function getAllConnections(): array
    {
        return $this->connections;
    }

    /**
     * Возвращает все аутентифицированные соединения.
     *
     * @return SocketConnect[]
     */
    public function getAllAuthenticatedConnections(): array
    {
        $result = [];
        foreach ($this->connections as $conn) {
            if ($conn->isAuthenticated()) {
                $result[] = $conn;
            }
        }
        return $result;
    }

    /**
     * Возвращает все соединения конкретного пользователя.
     *
     * @return SocketConnect[]
     */
    public function getConnectionsByUserId(int $userId): array
    {
        $result = [];
        foreach ($this->connections as $conn) {
            if ($conn->isAuthenticated() && $conn->userId === $userId) {
                $result[] = $conn;
            }
        }
        return $result;
    }

    public function getConnectionsCountForUser(int $userId): int
    {
        return count($this->getConnectionsByUserId($userId));
    }

    public function authenticateConnection(int $id, int $userId): void
    {
        $conn = $this->getConnection($id);
        if ($conn !== null) {
            $conn->authenticate($userId);
        }
    }
}