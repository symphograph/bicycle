<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\Errors\Files\InvalidMD5;
use Symphograph\Bicycle\Errors\Files\UnknownTypeErr;
use Symphograph\Bicycle\FileHelper;
use Symphograph\Bicycle\PDO\DB;

class FileDTO implements FileITF
{
    use DTOTrait;

    const string tableName = 'Files';
    const array  types     = ['img', 'doc'];

    public int    $id;
    public string $md5;
    public string $ext;
    public string $type;
    public string $createdAt;
    public string $status;

    public static function byUploaded(TmpUploadFile $file): static
    {
        $md5 = $file->getMd5();
        $ext = $file->getExtension();
        return static::newInstance($md5, $ext);
    }

    public static function newInstance(string $md5, string $ext): static
    {
        $props = get_defined_vars();
        $hackIDENotice = func_num_args();

        $object = static::byBind($props);
        $object->validate();
        $object->fixExt();
        return $object;
    }

    public function validate(): void
    {
        if (!$this->isValidMD5()) {
            throw new InvalidMD5();
        }

        if (!in_array($this->type, self::types)) {
            throw new UnknownTypeErr();
        }
    }

    private function isValidMD5(): bool
    {
        return preg_match('/^[a-f0-9]{32}$/', $this->md5) === 1;
    }

    private function fixExt(): void
    {
        $this->ext = strtolower($this->ext);
        if ($this->ext === 'jpeg') $this->ext = 'jpg';
    }

    public static function byNameWithMD5(string $baseName): static
    {
        $md5 = pathinfo($baseName, PATHINFO_FILENAME);
        $ext = pathinfo($baseName, PATHINFO_EXTENSION);
        return static::newInstance($md5, $ext);
    }

    public function getFullPath(): string
    {
        $relPath = $this->getRelPath();
        return FileHelper::fullPath($relPath, false);
    }

    public function getRelPath(): string
    {
        $md5Path = FileHelper::getMD5Path($this->md5);
        return static::mainFolder . '/' . $md5Path . '/' . $this->nameByMD5();
    }

    public function nameByMD5(): string
    {
        return $this->md5 . ($this->ext ? '.' . $this->ext : '');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function updateStatus(FileStatus $status): void
    {
        $sql = "update Files set status = :status where id = :id";
        $params = ['status' => $status->value, 'id' => $this->id];
        DB::qwe($sql, $params);
        $this->status = $status->value;
    }

    protected function afterPut(): void
    {
        $this->id = self::idByPut();
    }

    public function idByPut(): int
    {
        $lastId = DB::lastId();
        if ($lastId) {
            return $lastId;
        }

        $file = self::byMD5($this->md5);
        return $file->id;
    }

    public static function byMD5($md5): static|false
    {
        $sql = "select * from Files where md5 = :md5";
        $params = ['md5' => $md5];
        $qwe = DB::qwe($sql, $params);
        return $qwe->fetchObject(static::class);
    }

    protected function beforePut(): void
    {
        $this->validate();
    }

    protected function beforeDel()
    {
    }

    protected function afterDel()
    {
    }

    public static function createTable(): void
    {
        $sql = "create table if not exists Files
            (
                id        bigint unsigned auto_increment
                    primary key,
                md5       char(32) charset ascii                              not null,
                ext       char(4) charset ascii     default ''                not null,
                type      varchar(16) charset ascii                           not null,
                createdAt timestamp                 default CURRENT_TIMESTAMP not null,
                status    varchar(32) charset ascii default 'uploaded'        not null,
                constraint md5
                    unique (md5, ext)
            )
                engine = InnoDB;";
        DB::qwe($sql);
    }
}