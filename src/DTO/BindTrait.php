<?php

namespace Symphograph\Bicycle\DTO;

use ReflectionObject;

trait BindTrait
{
    public function bindSelf(object|array $object): void
    {
        $object = (object) $object;

        foreach ($object as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    public static function byArr(object|array $object): self
    {
        $self = new self();
        $self->bindSelf($object);
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

    public function unsetEmptyProps(): void
    {
        foreach ($this as $k => $v) {
            if(!empty($v)) continue;
            unset($this->$k);
        }
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

}