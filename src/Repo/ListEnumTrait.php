<?php

namespace Symphograph\Bicycle\Repo;

use Symphograph\Bicycle\Repo\Redis\RedisTTL;

/**
 * @mixin ListEnumITF
 */
trait ListEnumTrait
{
    private const        defaultTTL = RedisTTL::_1day;
    public function key(array $params = []): string
    {
        if (!defined(static::class . '::keyPrefix')) {
            throw new \Exception('keyPrefix constant must be defined');
        }

        $baseKey = static::keyPrefix . ':' . $this->value;
        $propNames = $this->propNames();

        if (empty($propNames)) {
            return $baseKey;
        }

        $suffixParts = [];
        foreach ($propNames as $name) {
            if (!array_key_exists($name, $params) || $params[$name] === null) {
                throw new \InvalidArgumentException(
                    "Missing required parameter '{$name}' for list '{$this->value}'"
                );
            }
            $value = $params[$name];
            $suffixParts[] = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
        }

        return $baseKey . ':' . implode(':', $suffixParts);
    }

    public function pattern(): string
    {
        if (!defined(static::class . '::keyPrefix')) {
            throw new \Exception('keyPrefix constant must be defined');
        }

        $baseKey = static::keyPrefix . ':' . $this->value;
        return empty($this->propNames()) ? $baseKey : $baseKey . ':*';
    }

    public function method(): string
    {
        return 'list' . ucfirst($this->value);
    }

    /**
     * Возвращает список имён свойств, по которым строится список.
     * По умолчанию использует устаревший метод propName(), если он определён.
     * Если метод propName() не определён, возвращает пустой массив.
     *
     * @return string[]
     */
    public function propNames(): array
    {
        if (method_exists($this, 'propName')) {
            $propName = $this->propName();
            return $propName === null ? [] : [$propName];
        }
        return [];
    }

    public function ttl(): RedisTTL
    {
        return static::defaultTTL;
    }
}