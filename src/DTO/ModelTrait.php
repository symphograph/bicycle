<?php

namespace Symphograph\Bicycle\DTO;

trait ModelTrait
{
    use BindTrait;
    public static function byId(int $id): self|false
    {
        $parent = parent::byId($id);
        if(!$parent) return false;
        return self::byBind($parent);
    }

    public static function byAccountID(int $accountId): self|false
    {
        $ObjectDTO = parent::byAccountId($accountId);
        if(!$ObjectDTO) return false;
        $selfObject = new self();
        $selfObject->bindSelf($ObjectDTO);
        return $selfObject;
    }

    public static function byIdAndInit(int $id): self|bool
    {
        $selfObject = self::byId($id);
        if(!$selfObject) return false;
        if(method_exists(self::class, 'initData')){
            $selfObject->initData();
        }
        return $selfObject;
    }

    /**
     * @param object[] $objects
     * @return object[]
     */
    public static function initDataInList(array $objects): array
    {
        $arr = [];

        foreach ($objects as $object){
            if(method_exists($object::class, 'initData')){
                $object->initData();
            }
            $arr[] = $object;
        }
        return $arr;
    }

    public function putToDB(): void
    {
        if(method_exists(self::class, 'beforePut')){
            $this->beforePut();
        }

        $parent = parent::byBind($this);
        $parent->putToDB();

        if(method_exists(self::class, 'afterPut')){
            $this->afterPut();
        }
    }
}