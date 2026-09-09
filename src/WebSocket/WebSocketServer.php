<?php
declare(strict_types=1);

namespace Symphograph\Bicycle\WebSocket;

use Channel\Client as ChannelClient;
use Channel\Server as ChannelServer;
use Symphograph\Bicycle\Errors\Handler;
use Symphograph\Bicycle\Repo\Redis\RedisDB;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

class WebSocketServer
{
    private WebSocketConfig $config;
    private ConnectionManager $connectionManager;
    private Authenticator $authenticator;
    private MessageHandler $messageHandler;
    private HeartbeatManager $heartbeatManager;

    public function __construct(
        ?WebSocketConfig $config = null,
        ?ConnectionManager $connectionManager = null,
        ?Authenticator $authenticator = null,
        ?MessageHandler $messageHandler = null,
        ?HeartbeatManager $heartbeatManager = null,
    ) {
        $this->config = $config ?? WebSocketConfig::fromEnv();

        $this->connectionManager = $connectionManager ?? new ConnectionManager();
        $this->authenticator     = $authenticator     ?? new Authenticator($this->connectionManager);
        $this->messageHandler    = $messageHandler    ?? new MessageHandler($this->connectionManager, $this->authenticator);
        $this->heartbeatManager  = $heartbeatManager  ?? new HeartbeatManager($this->connectionManager);
    }

    public function run(): void
    {
        $this->configureChannelServer();
        $this->configureWebSocketWorker();
        $this->configureRedisSubscriber();
        Worker::runAll();
    }

    private function configureChannelServer(): void
    {
        $channel = new ChannelServer(
            ip: $this->config->getChannelHost(),
            port: $this->config->getChannelPort()
        );
        SocketConnect::log("ChannelServer started on port {$this->config->getChannelPort()}");
    }

    private function configureWebSocketWorker(): void
    {
        $ws = new Worker(
            "websocket://{$this->config->getSocketHost()}:{$this->config->getSocketPort()}"
        );
        $ws->name = 'WebSocket';
        $ws->maxSendBufferSize = $this->config->getMaxSendBufferSize();

        $ws->onWorkerStart = function () {
            $this->onWebSocketWorkerStart();
        };
        $ws->onConnect = function (TcpConnection $conn) {
            $this->onConnect($conn);
        };
        $ws->onMessage = function (TcpConnection $conn, $msg) {
            $this->messageHandler->handle($conn, $msg);
        };
        $ws->onClose = function (TcpConnection $conn) {
            $this->onClose($conn);
        };
        $ws->onError = function (TcpConnection $conn, $code, $msg) {
            $this->onError($conn, $code, $msg);
        };
    }

    private function onWebSocketWorkerStart(): void
    {
        SocketConnect::log('WebSocket worker started');

        ChannelClient::connect(
            ip: $this->config->getChannelClientHost(),
            port: $this->config->getChannelPort()
        );
        SocketConnect::log('WebSocket connected to Channel');

        $this->heartbeatManager->start();

        // Подписка на персональные события
        ChannelClient::on($this->config->getChannelEventName(), function ($data) {
            $this->handleUserChannelEvent($data);
        });

        // Подписка на broadcast-события
        ChannelClient::on($this->config->getChannelBroadcastEventName(), function ($data) {
            $this->handleBroadcastChannelEvent($data);
        });

        Handler::workerSet('socket');
    }

    /**
     * Обработка персонального события из канала.
     */
    private function handleUserChannelEvent(array $data): void
    {
        $uid = $data['uid'] ?? null;
        $msg = $data['msg'] ?? '';

        if ($uid === null) {
            SocketConnect::log('sendToUser: missing uid');
            return;
        }

        $userConnections = $this->connectionManager->getConnectionsByUserId($uid);
        if (empty($userConnections)) {
            SocketConnect::log("sendToUser: user $uid has no active connections");
            return;
        }

        $count = 0;
        foreach ($userConnections as $sConn) {
            $sConn->send($msg);
            $count++;
        }
        //SocketConnect::log("sendToUser: sent to $count connections of user $uid");
    }

    /**
     * Обработка broadcast-события из канала.
     */
    private function handleBroadcastChannelEvent(array $data): void
    {
        $msg = $data['msg'] ?? '';
        $connections = $this->connectionManager->getAllAuthenticatedConnections();
        foreach ($connections as $sConn) {
            $sConn->send($msg);
        }
        SocketConnect::log("Broadcast sent to " . count($connections) . " connections");
    }

    private function onConnect(TcpConnection $conn): void
    {
        $this->connectionManager->addConnection($conn);

        $conn->authTimer = Timer::add($this->config->getAuthTimeoutSeconds(), function () use ($conn) {
            SocketConnect::log("Auth timeout for client {$conn->id}");
            $conn->close();
            $this->connectionManager->removeConnection($conn->id);
        });

        SocketConnect::log("Client connected: {$conn->id}, waiting for auth");
    }

    private function onClose(TcpConnection $conn): void
    {
        if (isset($conn->authTimer)) {
            Timer::del($conn->authTimer);
        }

        $sConn = $this->connectionManager->getConnection($conn->id);
        if ($sConn === null) {
            return;
        }

        if ($sConn->isAuthenticated()) {
            $userId = $sConn->userId;
            $remaining = $this->connectionManager->getConnectionsCountForUser($userId) - 1;
            SocketConnect::log("Authenticated client disconnected: {$sConn->getInfo()}");
            if ($remaining > 0) {
                SocketConnect::log("User $userId has $remaining remaining connections");
            } else {
                SocketConnect::log("User $userId has no more active connections");
            }
        } else {
            SocketConnect::log("Unauthenticated client disconnected: {$conn->id}");
        }

        $this->connectionManager->removeConnection($conn->id);
    }

    private function onError(TcpConnection $conn, int $code, string $msg): void
    {
        $sConn = $this->connectionManager->getConnection($conn->id);
        $info = $sConn ? $sConn->getInfo() : "Unknown connection {$conn->id}";
        SocketConnect::log("WebSocket error: $code - $msg for $info");
    }

    private function configureRedisSubscriber(): void
    {
        $sub = new Worker();
        $sub->name = 'RedisSubscriber';

        $sub->onWorkerStart = function () {
            $this->onRedisWorkerStart();
        };
    }

    private function onRedisWorkerStart(): void
    {
        ChannelClient::connect(
            ip: $this->config->getChannelClientHost(),
            port: $this->config->getChannelPort()
        );
        SocketConnect::log('RedisSubscriber connected to Channel');

        $redis = RedisDB::client();

        // Обработка очереди персональных сообщений
        Timer::add($this->config->getRedisPollIntervalSeconds(), function () use ($redis) {
            $this->processUserRedisQueue($redis);
        });

        // Обработка очереди broadcast-сообщений
        Timer::add($this->config->getRedisPollIntervalSeconds(), function () use ($redis) {
            $this->processBroadcastRedisQueue($redis);
        });

        // Проверка длины очередей
        Timer::add($this->config->getRedisQueueCheckIntervalSeconds(), function () use ($redis) {
            $userQueueLength = $redis->lLen($this->config->getRedisQueueName());
            if ($userQueueLength > $this->config->getRedisQueueWarningThreshold()) {
                SocketConnect::log("WARNING: {$this->config->getRedisQueueName()} queue length: $userQueueLength");
            }

            $broadcastQueueLength = $redis->lLen($this->config->getRedisBroadcastQueueName());
            if ($broadcastQueueLength > $this->config->getRedisQueueWarningThreshold()) {
                SocketConnect::log("WARNING: {$this->config->getRedisBroadcastQueueName()} queue length: $broadcastQueueLength");
            }
        });

        Handler::workerSet('subscriber');
    }

    /**
     * Обрабатывает очередь персональных сообщений.
     */
    private function processUserRedisQueue($redis): void
    {
        $processed = 0;
        $maxMessages = $this->config->getRedisMaxMessagesPerTick();

        while ($processed < $maxMessages) {
            $msg = $redis->lPop($this->config->getRedisQueueName());
            if (!$msg) break;

            $processed++;
            $data = json_decode($msg, true);
            if (empty($data['userId'])) {
                SocketConnect::log('RedisSubscriber: missing userId in user queue');
                continue;
            }

            ChannelClient::publish($this->config->getChannelEventName(), [
                'uid' => $data['userId'],
                'msg' => $msg
            ]);
        }
    }

    /**
     * Обрабатывает очередь broadcast-сообщений.
     */
    private function processBroadcastRedisQueue($redis): void
    {
        $processed = 0;
        $maxMessages = $this->config->getRedisMaxMessagesPerTick();

        while ($processed < $maxMessages) {
            $msg = $redis->lPop($this->config->getRedisBroadcastQueueName());
            if (!$msg) break;

            $processed++;
            // Для broadcast не требуется userId
            ChannelClient::publish($this->config->getChannelBroadcastEventName(), [
                'msg' => $msg
            ]);
        }
    }
}