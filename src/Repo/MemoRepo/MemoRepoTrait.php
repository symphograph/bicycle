<?php

namespace Symphograph\Bicycle\Repo\MemoRepo;

use PDO;
use Symphograph\Bicycle\DTO\BindTrait;
use Symphograph\Bicycle\PDO\DB;

/**
 * @method static self byDTO($dto)
 */
trait MemoRepoTrait
{
    use BindTrait;

    /** @var array<int, static> */
    protected static array $memoRepo = [];

    private static bool $warmedUp = false;

    protected static function ensureWarmed(): void
    {
        if (!self::$warmedUp) {
            static::warmUpMemo();
            self::$warmedUp = true;
        }
    }

    public static function byId(int $id): ?static
    {
        self::ensureWarmed();
        return self::$memoRepo[$id];
    }

    private static function warmUpMemo(): void
    {
        $dtoClass = self::class . 'DTO';
        $tableName = $dtoClass::tableName;
        $sql = "select * from $tableName";
        $list = DB::qwe($sql)->fetchAll(PDO::FETCH_CLASS, $dtoClass);
        foreach ($list as $dto) {
            $self = self::byDTO($dto);
        }
        static::afterWarmUp();
        self::$warmedUp = true;
    }

    protected static function afterWarmUp(): void
    {

    }

    /**
     * @return static[]
     */
    public static function all(): array
    {
        static::ensureWarmed();
        return array_values(static::$memoRepo);
    }

    public static function byDTO(parent $dto): ?static
    {
        $obj = static::byBind($dto);
        self::$memoRepo[$dto->id] = $obj;
        return $obj;
    }
}
