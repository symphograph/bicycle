<?php

namespace Symphograph\Bicycle\Repo;

use Symphograph\Bicycle\DTO\BindITF;
use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\Repo\DB\AnyRepoDB;
use Symphograph\Bicycle\Repo\Redis\RedisDB;
use Symphograph\Bicycle\Repo\Redis\SimpleRedisRepo;

/**
 * Трейт, добавляющий функциональность кэшированных списков объектов.
 * Списки могут зависеть от нескольких параметров, определяемых через propNames() в enum.
 *
 * @template T of BindITF
 * @mixin HasClassITF<T>
 */
trait ListableRepoTrait
{
    protected AnyRepoDB       $db;
    protected SimpleRedisRepo $redis;

    /**
     * @var array<string, ListRepo<T>>
     */
    private array $listRepos = [];

    /**
     * Регистрирует ListRepo для указанного имени списка.
     *
     * @param ListRepo<T> $listRepo
     */
    private function regList(string $listName, ListRepo $listRepo): void
    {
        $this->listRepos[$listName] = $listRepo;
    }

    /**
     * Регистрирует все списки из enums().
     */
    protected function regLists(): void
    {
        /** @var class-string<T> $class */
        $class = static::class();

        foreach ($this->enums() as $listCase) {
            $listRepo = new ListRepo($listCase, $this->db, $class);
            $this->regList($listCase->value, $listRepo);
        }
    }

    /**
     * Возвращает все доступные перечисления списков.
     *
     * @return ListEnumITF[]
     */
    abstract protected function enums(): array;

    /**
     * Нормализует переданные параметры в ассоциативный массив,
     * сопоставляя позиционные значения с propNames() из enum.
     *
     * @param ListEnumITF $listEnum
     * @param array       $rawParams Может быть позиционным списком или ассоциативным массивом
     * @return array
     */
    protected function normalizeParams(ListEnumITF $listEnum, array $rawParams): array
    {
        $propNames = $listEnum->propNames();

        if (empty($propNames)) {
            if (!empty($rawParams)) {
                throw new \InvalidArgumentException(
                    "List {$listEnum->value} does not accept parameters"
                );
            }
            return [];
        }

        // Если параметры переданы как именованные (ассоциативный массив)
        if (array_keys($rawParams) !== range(0, count($rawParams) - 1)) {
            foreach ($propNames as $name) {
                if (!array_key_exists($name, $rawParams)) {
                    throw new \InvalidArgumentException(
                        "Missing parameter '{$name}' for list {$listEnum->value}"
                    );
                }
            }
            // Возвращаем только нужные ключи
            return array_intersect_key($rawParams, array_flip($propNames));
        }

        // Позиционные параметры – количество должно совпадать
        if (count($rawParams) !== count($propNames)) {
            throw new \InvalidArgumentException(
                "List {$listEnum->value} expects " . count($propNames) .
                " parameters, got " . count($rawParams)
            );
        }

        return array_combine($propNames, $rawParams);
    }

    /**
     * Возвращает полные объекты по списку.
     *
     * @return T[]
     */
    public function getList(ListEnumITF $listEnum, mixed ...$params): array
    {
        $params = $this->normalizeParams($listEnum, $params);
        $listRepo = $this->getListRepo($listEnum);

        // Пытаемся получить ID из кеша
        $cachedIds = $listRepo->getCachedIds($params);

        if ($cachedIds !== null) {
            return $this->listByIds($cachedIds);
        }

        // Если нет в кэше, получаем объекты напрямую из БД
        $objects = $listRepo->fetchObjectsFromDB($params);

        // Сохраняем ID в кеш списка как Redis Set
        $ids = array_map(fn($object) => $object->id, $objects);
        $listRepo->storeList($ids, $params);

        // Сохраняем отдельные объекты в Redis (если их еще нет)
        foreach ($objects as $object) {
            $this->redis->set($object);
        }

        return $objects;
    }

    /**
     * Возвращает только ID списка.
     *
     * @return int[]
     */
    public function getListIds(ListEnumITF $listEnum, mixed ...$params): array
    {
        $params = $this->normalizeParams($listEnum, $params);
        $listRepo = $this->getListRepo($listEnum);

        $cachedIds = $listRepo->getCachedIds($params);

        if ($cachedIds !== null) {
            return $cachedIds;
        }

        $ids = $listRepo->fetchIdsFromDB($params);
        $listRepo->storeList($ids, $params);

        return $ids;
    }

    /**
     * Получает объекты по массиву ID, сохраняя порядок.
     *
     * @param int[] $ids
     * @return T[]
     */
    public function listByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        // Разбиваем на пачки для избежания больших MGET запросов
        $chunks = array_chunk($ids, 100000);
        $allObjects = [];
        $allMissingIds = [];

        /** @var class-string<T> $class */
        $class = static::class();

        foreach ($chunks as $chunk) {
            $keys = $this->redis->keys($chunk);
            $items = RedisDB::mget($keys);

            $objects = [];
            $missingIds = [];

            // Собираем найденные объекты
            foreach ($items as $index => $item) {
                if ($item === false || $item === null) {
                    $missingIds[] = $chunk[$index];
                    continue;
                }

                $obj = $item;
                if ($obj instanceof $class) {
                    $objects[$obj->id] = $obj;
                }
            }

            // Загружаем отсутствующие
            if (!empty($missingIds)) {
                $missingObjects = $this->db->listByIds($missingIds);

                foreach ($missingObjects as $obj) {
                    $objects[$obj->id] = $obj;
                    $this->redis->set($obj);
                }
            }

            $allObjects = $allObjects + $objects;
            $allMissingIds = array_merge($allMissingIds, $missingIds);
        }

        // Сохраняем порядок из исходного массива ids
        $result = [];
        foreach ($ids as $id) {
            if (isset($allObjects[$id])) {
                $result[] = $allObjects[$id];
            }
        }

        return $result;
    }

    /**
     * Возвращает все объекты (список 'all').
     *
     * @return T[]
     */
    public function all(): array
    {
        foreach ($this->enums() as $case) {
            if ($case->value === 'all') {
                return $this->getList($case);
            }
        }

        return $this->db()->all();

        //throw new MyErrors("EnumListAll non implemented");
    }

    /**
     * Возвращает количество элементов в списке.
     */
    public function countList(ListEnumITF $listEnum, mixed ...$params): int
    {
        $params = $this->normalizeParams($listEnum, $params);
        $listRepo = $this->getListRepo($listEnum);

        $cachedCount = $listRepo->countCached($params);
        if ($cachedCount !== null) {
            return $cachedCount;
        }

        // Если нет в кэше, получаем полный список и кэшируем
        $this->getList($listEnum, ...array_values($params));

        return $listRepo->countCached($params) ?? 0;
    }

    /**
     * Инвалидирует кэш списка для конкретных параметров.
     */
    public function invalidateList(ListEnumITF $listEnum, mixed ...$params): void
    {
        $params = $this->normalizeParams($listEnum, $params);
        $this->getListRepo($listEnum)->invalidate($params);
    }

    /**
     * Очищает ВСЕ ключи для указанного списка (все ID).
     */
    public function clearListCompletely(ListEnumITF $listEnum): void
    {
        $this->getListRepo($listEnum)->invalidateAll();
    }

    /**
     * Проверить наличие объекта в кэшированном списке.
     * Возвращает null, если кэш отсутствует.
     */
    public function isInList(ListEnumITF $listEnum, int $itemId, mixed ...$params): ?bool
    {
        $params = $this->normalizeParams($listEnum, $params);
        return $this->getListRepo($listEnum)->isInCachedList($itemId, $params);
    }

    /**
     * Инвалидирует списки, зависящие от полей, изменённых в объекте.
     */
    public function clearOnCRUD(BindITF $data): void
    {
        foreach ($this->enums() as $case) {
            $propNames = $case->propNames();

            if (empty($propNames)) {
                // Список без параметров – просто инвалидируем
                $this->invalidateList($case);
                continue;
            }

            // Собираем параметры из объекта
            $params = [];
            foreach ($propNames as $name) {
                if (property_exists($data, $name)) {
                    $params[$name] = $data->{$name};
                }
            }

            // Проверяем, что все необходимые параметры присутствуют
            $allPresent = true;
            foreach ($propNames as $name) {
                if (!array_key_exists($name, $params) || $params[$name] === null) {
                    $allPresent = false;
                    break;
                }
            }

            if ($allPresent) {
                $this->invalidateList($case, ...array_values($params));
            }
        }
    }

    /**
     * Очищает ВСЕ списки полностью (все варианты, все ID).
     */
    public function clearAllLists(): void
    {
        foreach ($this->enums() as $listCase) {
            $this->clearListCompletely($listCase);
        }
    }

    /**
     * Возвращает зарегистрированный ListRepo по enum.
     *
     * @return ListRepo<T>
     */
    private function getListRepo(ListEnumITF $listEnum): ListRepo
    {
        $listName = $listEnum->value;
        if (!isset($this->listRepos[$listName])) {
            throw new \InvalidArgumentException("List repo '$listName' not registered");
        }

        return $this->listRepos[$listName];
    }

    /**
     * Возвращает объекты, обновлённые после указанной даты.
     *
     * @return T[]
     */
    public function listOfUpdated(\DateTime $fromDate): array
    {
        $ids = $this->db->listUpdatedIds($fromDate);
        return $this->listByIds($ids);
    }

    /**
     * Возвращает объекты, обновлённые после указанной даты и принадлежащие пользователю.
     *
     * @return T[]
     */
    public function listOfUpdatedPrivate(\DateTime $fromDate, int $createdBy): array
    {
        $ids = $this->db->listUpdatedPrivateIds($fromDate, $createdBy);
        return $this->listByIds($ids);
    }
}