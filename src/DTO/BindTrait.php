<?php

namespace Symphograph\Bicycle\DTO;

trait BindTrait
{
    public function bindSelf(object|array $Object): void
    {
        $Object = (object) $Object;
        $vars = get_class_vars($this::class);
        foreach ($vars as $k => $v) {
            if (!isset($Object->$k)) continue;
            $this->$k = $Object->$k;
        }
    }

    public static function byArr(object|array $Object): self
    {
        $self = new self();
        $self->bindSelf($Object);
        return $self;
    }
}