<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\HlModel;

/**
 * Запросы к highload блокам
 */
final class HlModelQuery
{
    /**
     * Класс модели
     * @var string
     */
    private string $model;

    /**
     * @var DataManager
     */
    private DataManager $modelEntity;

    /**
     * @param string $model Класс модели
     */
    public function __construct(string $model)
    {
        $this->model = $model;
        Helper::includeBaseModules();
        /**
         * @var HlModel $model
         */
        $model = new $this->model();
        if (! $model instanceof HlModel) {
            throw new SystemException('Model query must be instance of HlModel');
        }
        $this->modelEntity = new ($model::getEntityClass());
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
     * @return array
     */
    public function fetch(Builder $builder): array {
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
                    'offset' => $builder->getOffset()
                ],
                $cache
            )
        );
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             * @var HlModel $model
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
    public function count(Builder $builder): int {
        return $this->modelEntity::getCount($builder->getFilter());
    }
}
