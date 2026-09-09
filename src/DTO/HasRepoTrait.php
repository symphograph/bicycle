<?php

namespace Symphograph\Bicycle\DTO;

use Symphograph\Bicycle\Repo\RepoITF;
use Symphograph\Bicycle\Repo\RepoRegistry;

/** @template T of BindITF */
trait HasRepoTrait
{
    /** @var ?RepoITF<static> */
    public static ?RepoITF $repo = null;

    /** @return RepoITF<T> */
    static function repo(): RepoITF
    {
        return static::$repo ?? RepoRegistry::get(static::class);
    }

    /** @return T | null */
    public static function byId(int $id, bool $required = true): ?static
    {
        if(method_exists(static::class, 'byIdFromMemo')) {
            return static::byIdFromMemo($id, $required);
        }
        return static::repo()->byId($id, $required);
    }

    public function update(): void
    {
        static::repo()->update($this);
    }
    
    public function insert(bool $auto): void
    {
        static::repo()->insert($this, $auto);
    }

    public function delete(): void
    {
        static::repo()->delete($this);
    }

    public function getParamKey(): string
    {
        $params = $this->paramsForKey();
        ksort($params);
        return md5(serialize($params));
    }

    /** @return T[] */
    public function all(): array
    {
        return static::repo()->all();
    }

    public function broadcast(): void {}
}