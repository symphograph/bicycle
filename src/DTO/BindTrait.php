<?php

namespace Symphograph\Bicycle\DTO;

trait BindTrait
{
    public function bindSelf(object|array $object): void
    {
        $object = (object) $object;
        $vars = get_class_vars($this::class);
        foreach ($vars as $k => $v) {
            if (!isset($object->$k)) continue;
            $this->$k = $object->$k;
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
            $list[] = self::byBind($object);
        }
        return $list;
    }

    public function getAllProps(): array
    {
        return get_object_vars($this);
    }
}