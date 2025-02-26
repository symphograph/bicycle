<?php

namespace App\Files;

use Override;
use Symphograph\Bicycle\DTO\AbstractList;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Files\FileDoc;
use Symphograph\Bicycle\Files\FileDTO;
use Symphograph\Bicycle\Files\FileIMG;

class FileList extends AbstractList
{
    /**
     * @var FileIMG[] | FileDoc[]
     */
    protected array $list = [];

    #[Override] public static function getItemClass() : string
    {
        return FileDTO::class;
    }

    public static function all(): self
    {
        $sql = "select * from Files order by createdAt desc, id desc";
        $FileList = self::bySql($sql);
        $FileList->classMap();
        return $FileList;
    }

    private function classMap(): void
    {
        $list = [];
        foreach ($this->list as $object) {
            $list[] = match ($object->getType()) {
                'img' => FileIMG::byBind($object),
                'doc' => FileDoc::byBind($object),
                default => throw new AppErr('Unknown file type')
            };
        }
        $this->list = $list;
    }
}