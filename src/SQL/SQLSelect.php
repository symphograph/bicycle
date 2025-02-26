<?php

namespace Symphograph\Bicycle\SQL;

use JetBrains\PhpStorm\ExpectedValues;
use Symphograph\Bicycle\PDO\DB;

/**
 * @template T
 */
class SQLSelect
{
    private string $tableName  = '';
    private string $sql        = '';
    private array  $columns    = [];
    private array  $whereArr   = [];
    private string $selectStr  = '';
    private array  $orderByArr = [];
    private string $limitStr   = '';
    private array  $params     = [];

    /**
     * @template T
     * @var class-string<T> $className
     */
    private string $className;

    public static function byTable(string $tableName): self
    {
        $self = new self();
        $self->tableName = $tableName;
        return $self;
    }

    /**
    * @param class-string<T> $className
    */
    public static function byClass(string $className): self
    {
        $self = new self();
        $self->className = $className;
        $self->tableName = $className::tableName;
        return $self;
    }

    public function columns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function column(string $columnName): self
    {
        $this->columns[] = $columnName;
        return $this;
    }

    public function whereNotNull(string $columnName): self
    {
        $this->whereArr[] = $columnName . ' IS NOT NULL';
        return $this;
    }

    public function whereIsNull(string $columnName): self
    {
        $this->whereArr[] = $columnName . ' IS NULL';
        return $this;
    }

    public function where(
        string                $colName,
        #[ExpectedValues(values: ['=', '<', '<=', '>=', '!=', 'in'])]
        string                $operator,
        string|int|float|null $value = null, string $logic = 'AND'): self
    {
        if (in_array($operator, ['is null', 'is not null'])) {
            $condition = "$colName $operator";
        } else {
            $placeholder = ':' . $colName . count($this->params);
            $condition = "$colName $operator $placeholder";
            $this->params[$placeholder] = $value;
        }

        if (!empty($this->whereArr)) {
            $this->whereArr[] = PHP_EOL . "\r" . "$logic $condition";
        } else {
            $this->whereArr[] = $condition;
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderByArr[] = "$column $direction";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limitStr = PHP_EOL . "LIMIT $offset, $limit";
        return $this;
    }

    private function getSelectStr(): void
    {
        $colString = !empty($this->columns) ? implode(',', $this->columns) : '*';
        $this->selectStr = "SELECT $colString FROM $this->tableName";
    }

    private function getWhereStr(): string
    {
        return !empty($this->whereArr) ? PHP_EOL . 'WHERE ' . implode(' ', $this->whereArr) : '';
    }

    private function getOrderByStr(): string
    {
        return !empty($this->orderByArr) ? PHP_EOL . ' ORDER BY ' . implode(', ', $this->orderByArr) : '';
    }

    private function getLimitStr(): string
    {
        return $this->limitStr;
    }

    public function getQuery(): string
    {
        $this->getSelectStr();
        $whereStr = $this->getWhereStr();
        $orderByStr = $this->getOrderByStr();
        $limitStr = $this->getLimitStr();

        return $this->selectStr . $whereStr . $orderByStr . ' ' . $limitStr;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return ?T
     */
    public function exe(): ?object
    {
        $qwe = DB::qwe($this->getQuery(), $this->params);

        /** @var T $result */
        if(isset($this->className)) {
            $result = $qwe->fetchObject($this->className);
        } else {
            $result = $qwe->fetchObject();
        }
        return $result;
    }
}