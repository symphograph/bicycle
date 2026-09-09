<?php
declare(strict_types=1);

namespace Symphograph\Bicycle\WebSocket;

use Workerman\Timer;

class HeartbeatManager
{
    public function __construct(
        private ConnectionManager $connectionManager,
        private int               $interval = 10
    )
    {
    }

    public function start(): void
    {
        Timer::add($this->interval, function () {
            foreach ($this->connectionManager->getAllConnections() as $connId => $sConn) {
                if (!$sConn->isAuthenticated()) {
                    continue;
                }

                // Проверяем таймаут
                if (!$sConn->isPingOk()) {
                    SocketConnect::log("Client timeout: {$sConn->getInfo()}");
                    $sConn->close();
                    $this->connectionManager->removeConnection($connId);
                    continue;
                }

                // Отправляем ping если нужно
                if ($sConn->needsPing()) {
                    $sConn->sendPing();
                }
            }
        });
    }
}