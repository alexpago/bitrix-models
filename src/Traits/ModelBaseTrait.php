<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

/**
 * Вспомогательные методы модели
 */
trait ModelBaseTrait
{
    /**
     * @return $this
     */
    public function setFilter(array $filter): static
    {
        $this->queryFilter = $filter;

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit(int $limit): static
    {
        $this->queryLimit = $limit;

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): static
    {
        return $this->setLimit($limit);
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function setOffset(int $offset): static
    {
        $this->queryOffset = $offset;

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): static
    {
        return $this->setOffset($offset);
    }

    /**
     * @param array $select
     * @return $this
     */
    public function setSelect(array $select): static
    {
        $this->querySelect = $select;

        return $this;
    }

    /**
     * @param mixed ...$arguments
     * @return $this
     */
    public function select(...$arguments): static
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
     * Добавить кэширование запроса
     * @param int $ttl Время жизни кэша в секундах
     * @param bool $withJoin Кэшировать JOIN
     * @return $this
     */
    public function withCache(int $ttl = 3600, bool $withJoin = false): static
    {
        $this->cacheTtl = $ttl;
        $this->cacheJoin = $withJoin;

        return $this;
    }

    /**
     * Исключить кэширование запроса
     * @return $this
     */
    public function withoutCache(): static
    {
        $this->cacheTtl = 0;

        return $this;
    }
    
    /**
     * @param array $order
     * @return $this
     */
    public function setOrder(array $order): static
    {
        $this->queryOrder = $order;

        return $this;
    }

    /**
     * Сортировка
     * @param string $column
     * @param string $order
     * @return $this
     */
    public function order(string $column, string $order = 'asc'): static
    {
        $this->queryOrder[$column] = $order;

        return $this;
    }

    /**
     * Сортировка по убыванию
     * @param string $column
     * @return $this
     */
    public function orderDesc(string $column): static
    {
        return $this->order($column, 'desc');
    }
}
