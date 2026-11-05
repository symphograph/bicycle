<?php

namespace Symphograph\Bicycle\Repo\Redis;

use Redis;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Logs\Log;

class RedisDB
{
    private static ?Redis $client = null;

    /**
     * Получение клиента Redis (singleton)
     */
    public static function client(): Redis
    {
        if (self::$client) return self::$client;

        $config = RedisConfig::fromEnv();
        $redis = new Redis();

        // Подключение с таймаутом
        $redis->connect($config->host, $config->port, $config->timeout);

        // Аутентификация если требуется
        if ($config->auth) {
            $redis->auth($config->auth);
        }

        // Выбор базы данных
        $redis->select($config->database);

        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_MSGPACK);
        // Дополнительные опции для лучшей производительности
        $redis->setOption(Redis::OPT_TCP_KEEPALIVE, 1);
        $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);


        self::$client = $redis;

        return self::$client;
    }

    public static function evalScript(
        string $script,
        array $keys = [],
        array $args = []
    ): mixed {
        $numKeys = count($keys);
        $allArgs = array_merge($keys, $args);
        return self::client()->eval($script, $allArgs, $numKeys); //mixed
    }

    /**
     * Получить по ключу
     */
    public static function get(string $key): mixed
    {
        $val = self::client()->get($key);
        return $val === false ? null : $val;
    }

    /**
     * Установить строку
     */
    public static function set(string $key, mixed $value, RedisTTL $ttl = RedisTTL::persist): bool
    {
        if($ttl === RedisTTL::persist) return self::client()->set($key, $value);
        return self::client()->setex($key, $ttl->value, $value);
    }

    /**
     * Удалить ключ(и)
     */
    public static function del(string|array $key): bool
    {
        return self::client()->del($key) > 0;
    }

    public static function blPop(string $key, float|int|string $timeout): array|false|null
    {
        $client = self::client();
        $raw = $client->rawCommand('BLPOP', $key, $timeout);

        if ($raw === false || $raw === null) {
            return $raw;
        }

        if (is_array($raw) && count($raw) === 0) {
            return null;
        }

        // Проверяем, что ответ — массив с минимум двумя элементами
        if (!is_array($raw) || count($raw) < 2) {
            // Логируем неожиданный ответ для последующего анализа
            Log::msg('Unexpected BLPOP response', ['raw' => $raw], 'redis');
            return null; // или false, в зависимости от логики обработки
        }

        $serializer = $client->getOption(Redis::OPT_SERIALIZER);
        $value = self::unserializeValue($raw[1], $serializer);

        return [$raw[0], $value];
    }

    public static function lPush(string $key, ...$elements): false|int
    {
        $client = self::client();
        $serializer = $client->getOption(Redis::OPT_SERIALIZER);

        $serialized = [];
        foreach ($elements as $elem) {
            $serialized[] = self::serializeValue($elem, $serializer);
        }

        array_unshift($serialized, $key);
        return $client->rawCommand('LPUSH', ...$serialized);
    }

    private static function serializeValue($value, int $serializer): string
    {
        return match ($serializer) {
            Redis::SERIALIZER_PHP => serialize($value),
            Redis::SERIALIZER_JSON => json_encode($value),
            Redis::SERIALIZER_MSGPACK => msgpack_serialize($value),
            Redis::SERIALIZER_IGBINARY => igbinary_serialize($value),
            default => (string) $value, // SERIALIZER_NONE
        };
    }

    private static function unserializeValue(string $data, int $serializer): mixed
    {
        return match ($serializer) {
            Redis::SERIALIZER_PHP => unserialize($data),
            Redis::SERIALIZER_JSON => json_decode($data, true),
            Redis::SERIALIZER_MSGPACK => msgpack_unserialize($data),
            Redis::SERIALIZER_IGBINARY => igbinary_unserialize($data),
            default => $data, // SERIALIZER_NONE
        };
    }

    /**
     * Удалить несколько ключей
     */
    public static function delMultiple(array $keys): bool
    {
        return self::client()->del($keys) > 0;
    }

    /**
     * Проверить наличие ключа
     */
    public static function exists(string $key): bool
    {
        return self::client()->exists($key) > 0;
    }

    /**
     * Установить TTL для ключа
     */
    public static function setTTL(string $key, RedisTTL $ttl, ?TTLMode $mode = null): bool
    {
        return match ($ttl) {
            RedisTTL::persist => !!self::client()->persist($key),
            default => !!self::client()->expire($key, $ttl->value, $mode?->value),
        };
    }

    /**
     * Получить оставшееся время жизни ключа
     * Возвращает:
     * - null если ключ не существует
     * - -1 если TTL не установлен (ключ persistent)
     * - положительное число - секунды до истечения
     */
    public static function getTTL(string $key): ?int
    {
        $ttl = self::client()->ttl($key);
        if ($ttl === -2) {
            return null; // Ключ не существует
        }
        return $ttl;
    }

    /**
     * Добавить элемент в множество
     */
    public static function sadd(string $key, ...$members): bool
    {
        return !!self::client()->sAdd($key, ...$members);
    }

    /**
     * Удалить элемент из множества
     */
    public static function srem(string $key, int|string $member): bool
    {
        return self::client()->sRem($key, $member) > 0;
    }

    /**
     * Получить все элементы множества
     * @return string[]
     */
    public static function smembers(string $key): array
    {
        $members = self::client()->sMembers($key);
        return $members === false ? [] : $members;
    }

    /**
     * Проверить наличие элемента в множестве
     */
    public static function sismember(string $key, int|string $member): bool
    {
        return self::client()->sIsMember($key, $member);
    }

    /**
     * Получить количество элементов в множестве
     */
    public static function scard(string $key): int
    {
        return self::client()->sCard($key) ?: 0;
    }

    /**
     * Получить несколько значений по ключам
     * @param mixed[] $keys
     * @return array<mixed|null>
     */
    public static function mget(array $keys): array
    {
        $result = self::client()->mGet($keys);
        return $result === false ? [] : $result;
    }

    /**
     * Установить несколько значений
     */
    public static function mset(array $keyValues): bool
    {
        return self::client()->mSet($keyValues);
    }

    public static function setex(string $key, mixed $value, RedisTTL $ttl = RedisTTL::persist): bool
    {
        return self::client()->setex($key, $ttl->value, $value);
    }

    /**
     * Инкремент числового значения
     */
    public static function incr(string $key): int
    {
        return self::client()->incr($key);
    }

    /**
     * Инкремент на заданное значение
     */
    public static function incrBy(string $key, int $value): int
    {
        return self::client()->incrBy($key, $value);
    }

    public static function setCounter(string $key, int $value): void
    {
        self::client()->rawCommand('SET', $key, (string)$value);
    }

    public static function getCounter(string $key): ?int
    {
        $val = self::client()->rawCommand('GET', $key);
        if ($val === false || $val === null) {
            return null;
        }
        return (int)$val;
    }

    /**
     * Декремент числового значения
     */
    public static function decr(string $key): int
    {
        return self::client()->decr($key);
    }

    /**
     * Декремент на заданное значение
     */
    public static function decrBy(string $key, int $value): int
    {
        return self::client()->decrBy($key, $value);
    }

    /**
     * Получить ключи по шаблону
     * @return string[]
     */
    public static function keys(string $pattern): array
    {
        $keys = self::client()->keys($pattern);
        return $keys === false ? [] : $keys;
    }

    /**
     * Удалить ключи по шаблону
     */
    public static function deleteByPattern(string $pattern): int
    {
        $keys = self::client()->keys($pattern);
        if (empty($keys)) {
            return 0;
        }
        return self::client()->del($keys);
    }

    /**
     * Очистить текущую базу данных
     */
    public static function flushDb(): bool
    {
        if (Env::isProd()) {
            throw new AppErr('Can`t flushDb in prod');
        }
        return self::client()->flushDB();
    }

    /**
     * Очистить все базы данных Redis
     */
    public static function flushAll(): bool
    {
        if (Env::isProd()) {
            throw new AppErr('Can`t flushAll in prod');
        }
        return self::client()->flushAll();
    }

    /**
     * Получить информацию о сервере
     */
    public static function info(?string $section = null): array
    {
        $info = self::client()->info($section);
        return $info === false ? [] : $info;
    }

    /**
     * Получить размер базы данных
     */
    public static function dbSize(): int
    {
        return self::client()->dbSize() ?: 0;
    }

    /**
     * Проверить соединение с Redis
     */
    public static function ping(): bool
    {
        return self::client()->ping() === '+PONG';
    }

    /**
     * Закрыть соединение
     */
    public static function close(): bool
    {
        if (self::$client) {
            $result = self::$client->close();
            self::$client = null;
            return $result;
        }
        return true;
    }

    /**
     * Переподключиться к Redis
     */
    public static function reconnect(): bool
    {
        self::close();
        return self::ping();
    }

    /**
     * Безопасное получение ключей по шаблону с использованием SCAN
     */
    public static function keysByPattern(string $pattern): array
    {
        $keys = [];
        $iterator = null;
        $instance = self::client();
        do {
            // SCAN вместо KEYS для безопасности в production
            $result = $instance->scan($iterator, $pattern, 1000);
            if ($result !== false) {
                $keys = array_merge($keys, $result);
            }
        } while ($iterator > 0);

        return array_unique($keys);
    }

    /**
     * Создание пайплайна для выполнения нескольких команд
     * @return PipelineWrapper
     */
    public static function pipeline(): PipelineWrapper
    {
        return new PipelineWrapper();
    }

    /**
     * Извлекает элемент с максимальным score из ZSET-очереди.
     *
     * Использует rawCommand для обхода автоматической сериализации,
     * затем вручную десериализует member.
     *
     * @param string $key Ключ очереди
     * @return array{0: mixed, 1: float}|null  [member, score] или null, если очередь пуста
     */
    public static function zPopMax(string $key): ?array
    {
        $client = self::client();
        $raw = $client->rawCommand('ZPOPMAX', $key);

        // Ранний выход: команда не выполнена или очередь пуста
        if (!is_array($raw) || count($raw) < 2) {
            return null;
        }

        $serializer = $client->getOption(Redis::OPT_SERIALIZER);

        // Десериализуем member (первый элемент) и преобразуем score во float
        $member = self::unserializeValue($raw[0], $serializer);
        $score = (float) $raw[1];

        return [$member, $score];
    }

    public static function exec(): array|false|Redis
    {
        return self::client()->exec();
    }


}