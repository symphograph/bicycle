<?php

namespace Symphograph\Bicycle\DTO;

trait ModelTrait
{
    public static function byId(int $id): self|bool
    {
        $ObjectDTO = parent::byId($id);
        if(!$ObjectDTO) return false;
        $selfObject = new self();
        $selfObject->bindSelf($ObjectDTO);
        return $selfObject;
    }

    public static function byAccountID(int $accountId): self|bool
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
        $selfObject->initData();
        return $selfObject;
    }

    /**
     * @param self[] $objects
     * @return self[]
     */
    public static function initDataInList(array $objects): array
    {
        $arr = [];
        foreach ($objects as $object){
            $object->initData();
            $arr[] = $object;
        }
        return $arr;
    }

    public function putToDB(): void
    {
        $ObjectDTO = new parent();
        $ObjectDTO->bindSelf($this);
        $ObjectDTO->putToDB();
    }
}