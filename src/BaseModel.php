<?php

namespace Pago\Bitrix\Models;

/**
 * Базовый класс моделей
 */
abstract class BaseModel
{
    /**
     * @var array
     */
    protected array $queryFilter = [];

    /**
     * @var array
     */
    protected array $querySelect = ['*'];

    /**
     * @var array|string[]
     */
    protected array $queryOrder = ['ID' => 'DESC'];

    /**
     * @var int
     */
    protected int $queryLimit = 999_999_999_999;

    /**
     * @var int
     */
    protected int $queryOffset = 0;

    /**
     * @var bool
     */
    protected bool $queryIsInit = false;

    /**
     * Инициализация запроса
     * @return static
     */
    public static function query(): static
    {
        $static = new static();
        $static->queryIsInit = true;

        return $static;
    }

    /**
     * @param string $where
     * @param $operator
     * @param $data
     * @return $this
     */
    final public function where(string $where, $operator, $data = null): static
    {
        if (null === $data) {
            $data = $operator;
            $operator = '=';
        }
        $this->queryFilter[$operator . $where] = $data;

        return $this;
    }

    /**
     * @return $this
     */
    final public function setFilter(array $filter): static
    {
        $this->queryFilter = $filter;

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    final public function setLimit(int $limit): static
    {
        $this->queryLimit = $limit;

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    final public function limit(int $limit): static
    {
        return $this->setLimit($limit);
    }

    /**
     * @param int $offset
     * @return $this
     */
    final public function setOffset(int $offset): static
    {
        $this->queryOffset = $offset;

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    final public function offset(int $offset): static
    {
        return $this->setOffset($offset);
    }

    /**
     * @param array $select
     * @return $this
     */
    final public function setSelect(array $select): static
    {
        $this->querySelect = $select;

        return $this;
    }

    /**
     * @param mixed ...$arguments
     * @return $this
     */
    final public function select(...$arguments): static
    {
        foreach ($arguments as $select) {
            if (!is_array($select)) {
                $select = [$select];
            }
            foreach ($select as $item) {
                $this->querySelect[] = $item;
            }
        }

        return $this;
    }

    /**
     * @param array $order
     * @return $this
     */
    final public function setOrder(array $order): static
    {
        $this->queryOrder = $order;

        return $this;
    }

    /**
     * @param string $column
     * @param string $order
     * @return $this
     */
    final public function order(string $column, string $order = 'asc'): static
    {
        $this->queryOrder[$column] = $order;

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    final public function orderDesc(string $column): static
    {
        return $this->order($column, 'desc');
    }


    /**
     * @param array $data
     * @return $this
     */
    public function fill(array $data): static
    {
        foreach ($data as $parameter => $value) {
            $this->{$parameter} = $value;
        }

        return $this;
    }

    /**
     * @param string $parameter
     * @param $value
     * @return void
     */
    public function __set(string $parameter, $value): void
    {
        $this->{$parameter} = $value;
    }

    /**
     * @param string $parameter
     * @return mixed
     */
    public function __get(string $parameter)
    {
        if (property_exists($this, $parameter)) {
            return $this->{$parameter};
        }

        return null;
    }
}
