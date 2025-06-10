<?php

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Entity;
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
     * @var Builder
     */
    protected Builder $queryBuilder;

    /**
     * @param string $modelClass
     * @param Builder $queryBuilder
     */
    public function __construct(string $modelClass, Builder $queryBuilder)
    {
        $this->model = $modelClass;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Фасет GetCount
     * @return int
     */
    public function count(): int {
        return $this->getEntity()::getCount($this->queryBuilder->getFilter());
    }

    /**
     * @return array
     */
    public function fetch(): array
    {
        $data = [];
        $cache = [];
        if ($this->queryBuilder->getCacheTtl() > 0) {
            $cache = [
                'cache' => [
                    'ttl' => $this->queryBuilder->getCacheTtl(),
                    'cache_joins' => $this->queryBuilder->getCacheJoin()
                ]
            ];
        }
        $query = $this->getEntity()::getList(
            array_merge(
                [
                    'filter' => $this->queryBuilder->getFilter(),
                    'select' => $this->queryBuilder->getSelect(),
                    'order' => $this->queryBuilder->getOrder(),
                    'limit' => $this->queryBuilder->getLimit(),
                    'offset' => $this->queryBuilder->getOffset()
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
            $data[] = $model->setElement($model, $element, $this->queryBuilder);
        }
        // Уничтожим экземляр класса для следующей таблицы
        if ($this->getEntity() instanceof DynamicTable) {
            Entity::destroy($this->getEntity()::getEntity());
        }

        return $data;
    }
}
