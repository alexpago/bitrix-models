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
     * @param string $model Класс модели
     */
    public function __construct(string $model)
    {
        $this->model = $model;
        Helper::includeBaseModules();
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
     * @return array
     */
    public function fetch(Builder $builder): array {
        /**
         * @var HlModel $model
         */
        $model = new $this->model();
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
        $entity = $this->getEntityClass($model);
        $query = $entity::getList(
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
            $model = clone $model;
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
        $entity = $this->getEntityClass();

        return $entity::getList($parameters);
    }

    /**
     * Фасет GetCount
     * @param Builder $builder
     * @return int
     * @see DataManager::getCount()
     */
    public function count(Builder $builder): int {
        return $this->getEntityClass()::getCount($builder->getFilter());
    }

    /**
     * Получение экземпляра класса @see DataManager
     * @param HlModel|null $model
     * @return DataManager
     * @throws SystemException
     */
    private function getEntityClass(?HlModel $model = null): DataManager
    {
        if (null === $model) {
            $model = new $this->model();
        }
        /**
         * @var HlModel $model
         */
        $entity = $model::getEntityClass();
        if (null === $entity) {
            throw new SystemException(
                sprintf(
                    'Ошибка инициализации highload ID = %d',
                    $model::hlId()
                )
            );
        }

        return new $entity();
    }
}
