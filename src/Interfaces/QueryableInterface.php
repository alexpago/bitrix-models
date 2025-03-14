<?php

namespace Pago\Bitrix\Models\Interfaces;

use Pago\Bitrix\Models\Queries\Builder;

/**
 * Запросы к базе данных
 */
interface QueryableInterface
{
    /**
     * @param string $modelClass
     * @param Builder $queryBuilder
     */
    public function __construct(string $modelClass, Builder $queryBuilder);

    /**
     * Получение элементов
     * @return array
     */
    public function fetch(): array;

    /**
     * Количество элементов
     * @return int
     */
    public function count(): int;
}
