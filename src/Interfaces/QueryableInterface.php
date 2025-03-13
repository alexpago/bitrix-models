<?php

namespace Pago\Bitrix\Models\Interfaces;

use Bitrix\Main\ORM\Query\Result as QueryResult;
use Pago\Bitrix\Models\Queries\Builder;

/**
 * Запросы к базе данных
 */
interface QueryableInterface
{
    /**
     * @param string $model
     */
    public function __construct(string $model);

    /**
     * @param Builder $builder
     * @return array
     */
    public function fetch(Builder $builder): array;

    /**
     * @param array $parameters
     * @return QueryResult
     */
    public function getList(array $parameters = []): QueryResult;

    /**
     * @param Builder $builder
     * @return int
     */
    public function count(Builder $builder): int;
}