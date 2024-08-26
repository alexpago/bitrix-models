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
    public function fetch(
        array $filter = [],
        array $select = ['*'],
        array $order = [],
        int $limit = 999_999_999_999,
        int $offset = 0
    ): array {
        /**
         * @var HlModel $model
         */
        $model = new $this->model();
        $data = [];
        $entity = $this->getEntityClass($model);
        $query = $entity::getList([
            'filter' => $filter,
            'select' => $select,
            'order'  => $order,
            'limit'  => $limit,
            'offset' => $offset
        ]);
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             * @var HlModel $model
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
