<?php

namespace Symphograph\Bicycle\Files;

use Override;
use Symphograph\Bicycle\Errors\Files\FileProcessErr;
use Symphograph\Bicycle\Img\SizeManager;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\Worker\ExecCommandBuilder;
use Symphograph\Bicycle\DTO\AbstractList;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Throwable;

class ImgList extends AbstractList
{
    const string type = 'img';

    /**
     * @var FileDTO[]
     */
    protected array $list = [];


    #[Override] public static function getItemClass() : string
    {
        return FileDTO::class;
    }

    public static function all(): self
    {
        $sql = "select * from Files WHERE type = :type order by createdAt desc, id desc";
        return self::bySql($sql, ['type' => self::type]);
    }

    public static function unSized($limit = 10): self
    {
        $sql = "
            select * from Files 
                WHERE type = :type 
                and status in (:statuses)
                AND (processStartedAt IS NULL OR processStartedAt < NOW() - INTERVAL 5 SECOND)
            order by createdAt, id
            limit :limit
            FOR UPDATE SKIP LOCKED";
        $params = [
            'type' => self::type,
            'statuses' => [FileStatus::Uploaded->value, FileStatus::Failed->value],
            'limit'=> $limit
        ];
        return self::bySql($sql, $params);
    }

    public function makeSizes(array $sizes = []): void
    {
        foreach ($this->list as $fileDTO) {
            if($fileDTO->status === 'completed'){
                continue;
            }
            DB::safeTransaction();
            try {
                $file = FileManager::byFileDTO($fileDTO);
                new SizeManager($file)->run($sizes);
            } catch (Throwable $err) {
                DB::safeRollback();
                $fileDTO->updateStatus(FileStatus::Failed);
                continue;
            }
        }
    }

    /**
     * Асинхронный вызов
     */
    public static function runResizeWorker(): void
    {
        $path = dirname(ServerEnv::DOCUMENT_ROOT()) . '/workers/imgWorker.php';
        $log = dirname(ServerEnv::DOCUMENT_ROOT()) . '/logs/worker.txt';
        $execCommand = new ExecCommandBuilder($path)
            //->addArgument($FList->getList())
            ->setOutputRedirection($log)
            ->runInBackground();

        $command = $execCommand->getCommand();
        exec($command);
    }

    /**
     * @return FileDTO[]
     */
    public function getList(): array
    {
        return $this->list;
    }
}