<?php
declare(strict_types=1);

namespace Symphograph\Bicycle\WebSocket;

use Exception;
use Symphograph\Bicycle\Powers\PowEnumITF;
use Symphograph\Bicycle\Token\AccessToken;
use Workerman\Timer;
use Workerman\Connection\TcpConnection;

readonly class Authenticator
{
    /**@param PowEnumITF[] $requiredPowers */
    public function __construct(
        private ConnectionManager $connectionManager,
        private array             $requiredPowers = [],
    )
    {
    }

    public function authenticate(TcpConnection $conn, string $jwt): void
    {
        $sConn = $this->connectionManager->getConnection($conn->id);
        if ($sConn === null) {
            SocketConnect::log("Auth attempt for unknown connection: {$conn->id}");
            return;
        }

        try {
            $token = AccessToken::byJwt($jwt, $this->requiredPowers);
            $userId = $token->userId;

            $this->connectionManager->authenticateConnection($conn->id, $userId);
            $sConn->sendAuthOk();

            // Убираем таймер аутентификации
            if (isset($conn->authTimer)) {
                Timer::del($conn->authTimer);
                unset($conn->authTimer);
            }

            $connectionsCount = $this->connectionManager->getConnectionsCountForUser($userId);
            SocketConnect::log(
                "Client authenticated successfully: {$sConn->getInfo()}, user has $connectionsCount connections"
            );
        } catch (Exception $e) {
            SocketConnect::log("Auth failed for client {$conn->id}: " . $e->getMessage());
            $sConn->sendAuthFail();
            $conn->close();
            $this->connectionManager->removeConnection($conn->id);
        }
    }
}