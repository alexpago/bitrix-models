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
     * @var DynamicTable
     */
    private DynamicTable $modelEntity;

    /**
     * @param string $model Класс модели
     */
    public function __construct(string $model)
    {
        $this->model = $model;
        /**
         * @var TableModel $model
         */
        $model = new $this->model();
        $this->modelEntity = new DynamicTable();
        $this->modelEntity::$tableName = $model::getTableName();
        $this->modelEntity::$map = $model::getMap();
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
     * @param Builder $builder
     * @return TableModel[]
     */
    public function fetch(Builder $builder): array
    {
        $data = [];
        $cache = [];
        if ($builder->cacheTtl > 0) {
            $cache = [
                'cache' => [
                    'ttl' => $builder->cacheTtl,
                    'cache_joins' => $builder->cacheJoin
                ]
            ];
        }
        $query = $this->modelEntity::getList(
            array_merge(
                [
                    'filter' => $builder->getFilter(),
                    'select' => $builder->getSelect(),
                    'order' => $builder->getOrder(),
                    'limit' => $builder->getLimit(),
                    'offset' => $builder->getOffset(),
                ],
                $cache
            )
        );
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             * @var TableModel $model
             */
            $model = new $this->model();
            $data[] = $model->setElement($model, $element);
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
        return $this->modelEntity::getList($parameters);
    }

    /**
     * Фасет GetCount
     * @param Builder $builder
     * @return int
     * @see DataManager::getCount()
     */
    public function count(Builder $builder): int
    {
        return $this->modelEntity::getCount($builder->getFilter());
    }
}
