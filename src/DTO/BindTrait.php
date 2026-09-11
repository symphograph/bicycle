<?php

namespace Symphograph\Bicycle\DTO;

use ReflectionObject;


trait BindTrait
{
    public function bindSelf(object|array $data): void
    {
        $object = (object) $data;

        foreach ($object as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    public static function byArr(object|array $data): self
    {
        return static::fromArray($data);
    }

    private static function fromArray(object|array $data): static
    {
        $self = new static();
        $self->bindSelf($data);
        return $self;
    }

    public static function byBind(object|array $data): self
    {
        $self = new self();
        $self->bindSelf($data);
        return $self;
    }

    /**
     * @return self[]
     */
    public static function listByBind(array $objects): array
    {
        $list = [];
        foreach ($objects as $object){
            $list[] = static::byBind($object);
        }
        return $list;
    }

    public function getAllProps(): array
    {
        return get_object_vars($this);
    }

    public function unsetNulls(): static
    {
        foreach ($this as $k => $v) {
            if($v !== null) continue;
            unset($this->$k);
        }
        return $this;
    }

    public function unsetEmptyProps(): static
    {
        foreach ($this as $k => $v) {
            if(!empty($v)) continue;
            unset($this->$k);
        }
        return $this;
    }

    public function unsetAllProps(): void
    {
        foreach ($this as $k => $v) {
            unset($this->$k);
        }
    }

    public function bindEmptyValues(array|object $object): void
    {
        $object = (object) $object;
        foreach ($object as $k => $v) {
            if(!empty($this->$k)) continue;
            if (empty($object->$k)) continue;
            $this->$k = $object->$k;
        }
    }

    private function isPropExist(object $object, string $property): bool
    {
        if (!property_exists($object, $property)) {
            return false; // Свойства нет в объекте
        }

        $refObject = new ReflectionObject($object);
        if (!$refObject->hasProperty($property)) {
            return false;
        }

        $prop = $refObject->getProperty($property);

        // Проверка инициализации, применим только если свойство публичное
        if ($prop->isPublic()) {
            return $prop->isInitialized($object);
        }

        // Для приватных/защищенных свойств возврат false, чтобы избежать ошибок доступа
        return false;
    }

    public function toArray(): array {
        return $this->getAllProps();
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public static function byJson(string $json): static
    {
        $arr = json_decode($json, true);
        return static::byArr($arr);
    }

    protected function hashExclude(): array
    {
        return [];
    }

    public function hash(): string
    {
        $arr = $this->getAllProps();

        foreach ($this->hashExclude() as $key) {
            unset($arr[$key]);
        }

        ksort($arr);
        return md5(json_encode($this->normalizeForHash($arr)));
    }

    protected function normalizeForHash(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            $a = $value->toArray();
            ksort($a);
            return $this->normalizeForHash($a);
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->normalizeForHash($v);
            }
            return $out;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return $value;
    }

    /** @param static[] $list */
    public static function hashOfList(array $list, bool $fixOrder = false): string
    {
        $hashes = array_map(fn($item) => $item->hash(), $list);
        if (!$fixOrder) sort($hashes);
        return md5(implode('|', $hashes));
    }
}