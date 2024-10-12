<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Pago\Bitrix\Models\Helpers\DynamicTable;
use Pago\Bitrix\Models\TableModel;

/**
 * Запросы к таблицам
 */
final class TableModelQuery
{
    /**
     * Класс модели
     * @var string
     */
    private string $model;

    /**
     * @param string $model Класс модели
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * @param string $model Класс модели
     * @return self
     */
    public static function instance(string $model): self
    {
        return new self($model);
    }

    /**
     * @param array $filter
     * @param array $select
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @param int $cacheTtl
     * @param bool $cacheJoin
     * @return TableModel[]
     */
    public function fetch(
        array $filter = [],
        array $select = ['*'],
        array $order = [],
        int $limit = 999_999_999_999,
        int $offset = 0,
        int $cacheTtl = 0,
        bool $cacheJoin = false
    ): array {
        /**
         * @var TableModel $model
         */
        $model = new $this->model();
        $data = [];
        $cache = [];
        if ($cacheTtl > 0) {
            $cache = [
                'cache' => [
                    'ttl' => $cacheTtl,
                    'cache_joins' => $cacheJoin
                ]
            ];
        }
        $query = $this->getEntityClass()::getList(
            array_merge(
                [
                    'filter' => $filter,
                    'select' => $select,
                    'order' => $order,
                    'limit' => $limit,
                    'offset' => $offset
                ],
                $cache
            )
        );
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             * @var TableModel $model
             */
            $model = clone $model;
            $data[] = $model->setElement($element);
        }

        return $data;
    }

    /**
     * Фасет GetList
     * @param array $parameters
     * @return QueryResult
     * @see DataManager::getList()
     */
    public function getList(array $parameters = []): QueryResult
    {
        $entity = $this->getEntityClass();

        return $entity::getList($parameters);
    }

    /**
     * Фасет GetCount
     * @param array $filter
     * @return int
     * @see DataManager::getCount()
     */
    public function count(array $filter = []): int {
        $entity = $this->getEntityClass();

        return $entity::getCount($filter);
    }

    /**
     * Получение экземпляра класса
     * @return DynamicTable
     */
    private function getEntityClass(): DynamicTable
    {
        /**
         * @var TableModel $model
         */
        $model = new $this->model();
        $entity = new DynamicTable();
        $entity::$tableName = $model::getTableName();
        $entity::$map = $model::getMap();

        return $entity;
    }
}
