<?php

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Pago\Bitrix\Models\Helpers\DynamicTable;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\HlModel;
use Pago\Bitrix\Models\TableModel;

/**
 * Базовый класс Query
 */
abstract class BaseQuery
{
    /**
     * @return DataManager|CommonElementTable|DynamicTable
     */
    abstract public function getEntity();

    /**
     * Класс модели
     * @var string
     */
    protected string $model;

    /**
     * @param string $model Класс модели
     * @return static
     */
    public static function instance(string $model): static
    {
        return new static($model);
    }

    /**
     * @param string $model
     */
    public function __construct(string $model)
    {
        $this->model = $model;
        Helper::includeBaseModules();
    }

    /**
     * Фасет GetList
     * @param array $parameters
     * @return QueryResult
     */
    public function getList(array $parameters = []): QueryResult
    {
        return $this->getEntity()::getList($parameters);
    }

    /**
     * Фасет GetCount
     * @param Builder $builder
     * @return int
     */
    public function count(Builder $builder): int {
        return $this->getEntity()::getCount($builder->getFilter());
    }

    /**
     * @param Builder $builder
     * @return array
     */
    public function fetch(Builder $builder): array
    {
        $data = [];
        $cache = [];
        if ($builder->getCacheTtl() > 0) {
            $cache = [
                'cache' => [
                    'ttl' => $builder->getCacheTtl(),
                    'cache_joins' => $builder->getCacheJoin()
                ]
            ];
        }
        $query = $this->getEntity()::getList(
            array_merge(
                [
                    'filter' => $builder->getFilter(),
                    'select' => $builder->getSelect(),
                    'order' => $builder->getOrder(),
                    'limit' => $builder->getLimit(),
                    'offset' => $builder->getOffset()
                ],
                $cache
            )
        );
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             * @var HlModel|TableModel $model
             */
            $model = new $this->model();
            $data[] = $model->setElement($model, $element);
        }

        return $data;
    }
}
