<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\Files\FileErr;
use Symphograph\Bicycle\Errors\Files\FileHashIsInvalid;
use Symphograph\Bicycle\Errors\Files\FileMD5IsInvalid;
use Symphograph\Bicycle\Errors\Files\FileNotExistsErr;
use Symphograph\Bicycle\Errors\Files\FileNotExistsInDBErr;
use Symphograph\Bicycle\Errors\Files\FileNotExistsInPathErr;
use Symphograph\Bicycle\Errors\Files\FileTypeNotConsistentErr;
use Symphograph\Bicycle\Errors\Files\FileTypeUnknownErr;
use Symphograph\Bicycle\Files\Repo\FileRepoDB;
use Symphograph\Bicycle\Img\SizeManager;
use Symphograph\Bicycle\PDO\DB;
use Throwable;


class FileManager
{
    private function __construct(
        public FileHDD $fileHDD,
        public FileDTO $fileDTO
    ) {}

    public static function byHash(string $hash, bool $required = true): ?static
    {
        try {
            $fileDTO = FileRepoDB::byHash($hash);
            $fileHDD = FileHDD::byStorage(
                $fileDTO->hash,
                FileExt::from($fileDTO->ext),
                FileType::from($fileDTO->type)
            );
        } catch (FileNotExistsErr $e) {
            if ($required) throw $e;
            return null;
        }

        return new FileManager($fileHDD, $fileDTO);
    }


    /**
     * @param int $id
     * @param bool $required
     * @return static|null
     * @throws FileNotExistsErr
     * @throws FileNotExistsInDBErr
     */
    public static function byId(int $id, bool $required = true): ?static
    {
        $fileDTO = FileRepoDB::byId($id);
        return static::byFileDTO($fileDTO, $required);
    }

    public static function byFileDTO(FileDTO $fileDTO, bool $required = true): ?static
    {
        try {
            $fileHDD = FileHDD::byStorage(
                $fileDTO->hash,
                FileExt::from($fileDTO->ext),
                FileType::from($fileDTO->type)
            );
        } catch (FileNotExistsErr $e) {
            if ($required) throw $e;
            return null;
        }

        return new FileManager($fileHDD, $fileDTO);
    }

    /**
     * @throws FileTypeUnknownErr
     * @throws FileTypeNotConsistentErr
     * @throws AppErr
     * @throws FileNotExistsInPathErr
     * @throws FileHashIsInvalid
     * @throws FileErr
     * @throws FileMD5IsInvalid
     */
    public static function byExternal(string $externalUrl, bool $required = true): ?static
    {
        try {
            $data = file_get_contents($externalUrl);
        } catch (Throwable) {
            if ($required) throw $e;
            return null;
        }

        return static::create($data);
    }


    /**
     * @throws FileTypeUnknownErr
     * @throws AppErr
     * @throws FileTypeNotConsistentErr
     * @throws FileNotExistsInPathErr
     * @throws FileErr
     * @throws FileHashIsInvalid
     * @throws FileMD5IsInvalid
     */
    public static function create(string $data): static
    {
        $fileHDD = FileHDD::create($data); // return FileHDD or make error
        $fileDTO = FileDTO::newInstance($fileHDD);

        DB::safeTransaction();
        $fileDTO->putToDB();
        $fileHDD->moveFromTmp();
        DB::safeCommit();

        return new self($fileHDD, $fileDTO);
    }

    public function delete(): void
    {
        DB::pdo()->beginTransaction();
        if($this->fileHDD->type->value === FileType::Img->value) {
            SizeManager::delSizes($this->fileHDD);
        }
        $this->fileDTO->del();
        $this->fileHDD->delete();
        DB::pdo()->commit();
    }

    public function setAsPublic(): static
    {
        DB::pdo()->beginTransaction();
        $this->fileDTO->setAsPublic();
        $this->fileHDD->setAsPublic();
        if($this->fileHDD->type->value === FileType::Img->value) {
            SizeManager::setAsPublic($this->fileHDD);
        }
        DB::pdo()->commit();
        return $this;
    }

    public function setAsPrivate(): static
    {
        DB::pdo()->beginTransaction();
        $this->fileDTO->setAsPublic();
        $this->fileHDD->setAsPublic();
        if($this->fileHDD->type->value === FileType::Img->value) {
            SizeManager::setAsPrivate($this->fileHDD);
        }
        DB::pdo()->commit();
        return $this;
    }
}