<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

/**
 * Вспомогательные методы модели
 */
trait ModelBaseTrait
{
    /**
     * Загрузить properties для инфоблоков
     * @var bool
     */
    public bool $withProperties = false;

    /**
     * Загрузить ссылку на детальную страницу для инфоблоков
     * @var bool
     */
    public bool $withDetailPageUrl = false;

    /**
     * @return $this
     */
    public function setFilter(array $filter): static
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @return array
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit(int $limit): static
    {
        $this->limit = $limit;
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
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function setOffset(int $offset): static
    {
        $this->offset = $offset;

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
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param array $select
     * @return $this
     */
    public function setSelect(array $select): static
    {
        $this->select = $select;
        return $this;
    }

    /**
     * @param mixed ...$arguments
     * @return $this
     */
    public function select(...$arguments): static
    {
        foreach ($arguments as $select) {
            if (! is_array($select)) {
                $select = [$select];
            }
            foreach ($select as $item) {
                // Перечисление полей через запятую в одном аргументе
                if (str_contains($item, ',')) {
                    $this->select = array_merge(
                        $this->select,
                        array_map(fn($item) => trim($item), explode(',', $item))
                    );
                } else {
                    // Строки
                    $this->select[] = is_string($item) ? trim($item) : $item;
                }
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getSelect(): array
    {
        return $this->select ?: ['*'];
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
        $this->order = $order;
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
        $this->order[$column] = $order;
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

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * Включить свойства инфоблока (для инфоблоков)
     * @param bool $includeProperties
     * @return $this
     */
    public function withProperties(bool $includeProperties = true): static
    {
        $this->withProperties = $includeProperties;
        return $this;
    }

    /**
     * Получить элементы с загрузкой детальной страницы (для инфоблоков)
     * @return $this
     */
    public function withDetailPageUrl(): static
    {
        $this->withDetailPageUrl = true;
        return $this;
    }
}
