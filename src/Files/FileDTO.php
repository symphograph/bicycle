<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\PDO\DB;

class FileDTO
{
    use DTOTrait;

    // Методы для обмена данными с бд

    const string tableName = 'Files';

    public int             $id;
    public string          $hash;
    public string          $ext;
    public readonly string $type;
    public string          $createdAt;
    public string          $status;
    public ?string         $processStartedAt;
    public bool            $isPublic = false;

    public static function newInstance(FileHDD $fileHDD): static
    {
        $object = new static();
        $object->hash = $fileHDD->hash;
        $object->ext = $fileHDD->ext->value;
        $object->type = $fileHDD->type->value;

        return $object;
    }

    public function fileName(): string
    {
        $ext = !empty($this->ext->value) ? ".{$this->ext->value}" : '';
        return $this->hash . $ext;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function updateStatus(FileStatus $status): void
    {
        $processStartedAt = $status->value === FileStatus::Uploaded->value ? null : date('Y-m-d H:i:s');
        $sql = "update Files set status = :status, processStartedAt = :processStartedAt where id = :id";
        $params = [
            'status'           => $status->value,
            'processStartedAt' => $processStartedAt,
            'id'               => $this->id
        ];
        DB::qwe($sql, $params);
        $this->status = $status->value;
    }

    protected function afterPut(): void
    {
        $this->id = DB::lastId() ?? self::byHash($this->hash)->id;
    }

    public static function byHash($hash): ?static
    {
        $sql = "select * from Files where hash = :hash";
        $params = ['hash' => $hash];
        $qwe = DB::qwe($sql, $params);
        return $qwe->fetchObject(static::class) ?? null;
    }

    public function setAsPublic(): void
    {
        $this->isPublic = true;
        $this->putToDB();
    }

    public function setAsPrivate(): void
    {
        $this->isPublic = false;
        $this->putToDB();
    }

}