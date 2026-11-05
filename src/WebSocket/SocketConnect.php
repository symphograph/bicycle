<?php
// workers/SocketConnect.php

namespace Symphograph\Bicycle\WebSocket;

use Workerman\Connection\TcpConnection;
use Symphograph\Bicycle\Logs\Log;

class SocketConnect
{
    private TcpConnection $conn;
    public ?int $userId = null;
    public int $lastPong;
    private int $pingTimeout = 60;
    private int $pingInterval = 20;
    private ?int $lastPingSent = null;

    private string $realClientIp;
    private int $realClientPort;
    private array $httpHeaders = [];

    public function __construct(TcpConnection $conn)
    {
        $this->conn = $conn;
        $this->lastPong = time();
        $this->realClientIp = $this->conn->getRemoteIp();
        $this->realClientPort = $this->conn->getRemotePort();
        $this->extractHttpHeaders();
        self::log("SocketConnect created for client {$this->id()}");
    }

    public function id(): int
    {
        return $this->conn->id;
    }

    public function authenticate(int $userId): void
    {
        $this->userId = $userId;
        $this->lastPong = time();
        self::log("Client {$this->id()} authenticated as uid=$userId");
    }

    public function isAuthenticated(): bool
    {
        return isset($this->userId);
    }

    /**
     * Извлекает HTTP-заголовки из WebSocket handshake
     */
    private function extractHttpHeaders(): void
    {
        try {
            if (isset($this->conn->httpRequest)) {
                $request = $this->conn->httpRequest;
                $this->httpHeaders = $request->header();

                // Детальное логирование
                self::log("HTTP headers for client {$this->id()}: " .
                    json_encode($this->httpHeaders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

                $this->extractRealIpFromHeaders();
                $this->extractRealPortFromHeaders();

                // Логируем результат извлечения IP
                self::log("After header extraction - Real IP: {$this->realClientIp}, Port: {$this->realClientPort}");
            } else {
                self::log("No HTTP request available for client {$this->id()}, using connection IP: " .
                    $this->conn->getRemoteIp() . ":" . $this->conn->getRemotePort());
            }
        } catch (\Exception $e) {
            self::log("Error extracting HTTP headers: " . $e->getMessage());
        }
    }

    /**
     * Извлекает реальный IP из HTTP-заголовков
     */
    private function extractRealIpFromHeaders(): void
    {
        if (empty($this->httpHeaders)) {
            self::log("No HTTP headers available for client {$this->id()}");
            return;
        }

        $originalIp = $this->realClientIp;
        $originalPort = $this->realClientPort;

        self::log("Extracting real IP for client {$this->id()}. Current IP: {$originalIp}:{$originalPort}");
        self::log("Available headers: " . implode(', ', array_keys($this->httpHeaders)));

        $possibleHeaders = [
            'cf-connecting-ip',
            'x-real-ip',
            'x-forwarded-for',
            'x-forwarded',
            'forwarded',
            'client-ip',
            'true-client-ip',
        ];

        foreach ($possibleHeaders as $header) {
            $value = $this->getHeaderValue($header);

            if ($value) {
                self::log("Found header '{$header}': {$value}");

                if ($header === 'x-forwarded-for') {
                    $ips = explode(',', $value);
                    $ip = trim($ips[0]);
                } else {
                    $ip = trim($value);
                }

                // Удаляем порт если есть
                if (strpos($ip, ':') !== false) {
                    $parts = explode(':', $ip);
                    $ip = $parts[0];
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $this->realClientIp = $ip;
                    self::log("Using IP from header '{$header}': {$ip} (was: {$originalIp})");
                    return;
                } else {
                    self::log("Invalid IP from header '{$header}': {$ip}");
                }
            }
        }

        // Проверяем стандартные заголовки
        $standardHeaders = ['remote-addr', 'x-real-ip'];
        foreach ($standardHeaders as $header) {
            $value = $this->getHeaderValue($header);
            if ($value && filter_var($value, FILTER_VALIDATE_IP)) {
                $this->realClientIp = $value;
                self::log("Using IP from standard header '{$header}': {$value}");
                return;
            }
        }

        self::log("No valid IP found in headers for client {$this->id()}, keeping original: {$originalIp}");
    }

    /**
     * Получает значение заголовка без учета регистра
     */
    private function getHeaderValue(string $headerName): ?string
    {
        $lowerHeaderName = strtolower($headerName);

        // Ищем заголовок без учета регистра
        foreach ($this->httpHeaders as $key => $value) {
            if (strtolower($key) === $lowerHeaderName) {
                return is_array($value) ? $value[0] : $value;
            }
        }

        return null;
    }

    private function extractRealPortFromHeaders(): void
    {
        $possibleHeaders = ['x-real-port', 'x-forwarded-port'];

        foreach ($possibleHeaders as $header) {
            $value = $this->getHeaderValue($header);
            if ($value && is_numeric($value) && $value > 0 && $value <= 65535) {
                $this->realClientPort = (int)$value;
                self::log("Using port from header '{$header}': {$value}");
                return;
            }
        }

        // Если порт не нашли в заголовках, оставляем оригинальный
    }

    public function updatePong(): void
    {
        $this->lastPong = time();
        // self::log("Pong updated for uid={$this->userId}");
    }

    public function isPingOk(): bool
    {
        return (time() - $this->lastPong) <= $this->pingTimeout;
    }

    public function needsPing(): bool
    {
        if ($this->lastPingSent === null) {
            return (time() - $this->lastPong) >= $this->pingInterval;
        }
        return (time() - $this->lastPingSent) >= $this->pingInterval;
    }

    public function sendPing(): void
    {
        $this->lastPingSent = time();
        $this->send(json_encode(['type' => 'ping']));
        // self::log("Ping sent to uid={$this->userId}, lastPong=" . (time() - $this->lastPong) . "s ago");
    }

    public function send($data): void
    {
        $this->conn->send($data);
    }

    public function sendAuthOk(): void
    {
        $this->send(json_encode(['type' => 'auth.ok']));
    }

    public function sendAuthFail(): void
    {
        $this->send(json_encode(['type' => 'auth.fail']));
    }

    public function close(): void
    {
        $userIdInfo = $this->userId ? "user {$this->userId}" : "unauthenticated";
        self::log("Disconnecting client {$this->id()} ($userIdInfo)");
        $this->conn->close();
    }

    public function getClientInfo(): string
    {
        return "{$this->realClientIp}:{$this->realClientPort}";
    }

    public function getInfo(): string
    {
        $lastPongAgo = time() - $this->lastPong;

        $info = "Client {$this->id()}, uid={$this->userId}, lastPong={$lastPongAgo}s ago";
        $info .= ", remote_ip={$this->getClientInfo()}";

        // Показываем разницу между реальным IP и IP соединения
        $connIp = $this->conn->getRemoteIp();
        $connPort = $this->conn->getRemotePort();

        if ($connIp !== $this->realClientIp) {
            $info .= " (via proxy: {$connIp}:{$connPort})";
        }

        if ($this->lastPingSent) {
            $lastPingAgo = time() - $this->lastPingSent;
            $info .= ", lastPing={$lastPingAgo}s ago";
        }

        // Информация о заголовках
        if (!empty($this->httpHeaders)) {
            $info .= ", headers: " . count($this->httpHeaders);
        }

        return $info;
    }

    public static function log(string $msg): void
    {
        Log::msg(msg: $msg, logFolder: 'socket');
    }
}