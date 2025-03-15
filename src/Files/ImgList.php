<?php

namespace Symphograph\Bicycle\Files;

use Override;
use Symphograph\Bicycle\Worker\ExecCommandBuilder;
use Symphograph\Bicycle\DTO\AbstractList;
use Symphograph\Bicycle\Env\Server\ServerEnv;

class ImgList extends AbstractList
{
    const string type = 'img';
    /**
     * @var FileIMG[]
     */
    protected array $list = [];


    #[Override] public static function getItemClass() : string
    {
        return FileIMG::class;
    }

    public static function all(): self
    {
        $sql = "select * from Files WHERE type = :type order by createdAt desc, id desc";
        return self::bySql($sql, ['type' => self::type]);
    }

    public static function unSized($limit = 11): self
    {
        $sql = "
            select * from Files 
                WHERE type = :type 
                and status != :status 
                AND (processStartedAt IS NULL OR processStartedAt < NOW() - INTERVAL 5 MINUTE)
            order by createdAt, id
            limit :limit";
        $params = ['type' => self::type, 'status' => 'completed', 'limit'=> $limit];
        return self::bySql($sql, $params);
    }

    public function makeSizes(array $sizes = []): void
    {
        foreach ($this->list as $fileIMG) {
            if($fileIMG->status !== 'uploaded'){
                continue;
            }
            $fileIMG->makeSizes($sizes);
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
}