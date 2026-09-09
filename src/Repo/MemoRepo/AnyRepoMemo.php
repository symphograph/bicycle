<?php

namespace Symphograph\Bicycle\Repo\MemoRepo;

use Symphograph\Bicycle\Repo\HasRepoITF;

/**
 * @template T of HasRepoITF
 */
class AnyRepoMemo
{
    /** @var array<int, T> */
    protected static array $memo = [];
    protected static array $paramIndex = [];
    protected static bool $isWarmed = false;

    /**
     * @return class-string<T>
     */
    public static function class(): string
    {
        return '';
    }

    protected function ensureWarmed(): void
    {
        if (!static::$isWarmed) {
            $this->warmUpMemo();
            static::$isWarmed = true;
        }
    }

    private function warmUpMemo(): void
    {
        $all = static::class()::repo()->all();
        $arr = [];

        foreach ($all as $item) {
            $arr[$item->id] = $item;
        }
        ksort($arr);
        static::$memo = $arr;

        $this->afterWarmUp();
    }

    protected function afterWarmUp(): void{}

    /**
     * @return T|null
     */
    public function byIdFromMemo(int $id): ?object
    {
        $this->ensureWarmed();
        return static::$memo[$id] ?? null;
    }

    /**
     * @return T|null
     */
    public function byParamsFromMemo(array $params): ?object
    {
        $this->ensureWarmed();
        $key = $this->paramKey($params);

        $id = static::$paramIndex[$key] ?? null;
        if($id === null) return null;
        return $this->byIdFromMemo($id);
    }

    protected function warmIndex(): void
    {
        foreach (static::$memo as $item) {
            $key = $this->paramKey($item->paramsForKey());
            static::$paramIndex[$key] = $item->id;
        }
    }

    public function paramKey(array $params): string
    {
        ksort($params);
        return md5(serialize($params));
    }

    public function clear(): void
    {
        static::$memo = [];
        static::$paramIndex = [];
        static::$isWarmed = false;
    }
}
//-------------------------------------------------------------

