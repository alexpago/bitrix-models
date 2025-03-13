<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Pago\Bitrix\Models\Helpers\DynamicTable;
use Pago\Bitrix\Models\Interfaces\QueryableInterface;
use Pago\Bitrix\Models\TableModel;

/**
 * Запросы к таблицам
 */
final class TableModelQuery extends BaseQuery implements QueryableInterface
{
    /**
     * @var DynamicTable
     */
    protected DynamicTable $modelEntity;

    /**
     * @param string $model Класс модели
     */
    public function __construct(string $model)
    {
        /**
         * @var TableModel $entity
         */
        $entity = new $model;
        $this->modelEntity = new DynamicTable();
        $this->modelEntity::$tableName = $entity::getTableName();
        $this->modelEntity::$map = $entity::getMap();
        parent::__construct($model);
    }

    /**
     * @return DynamicTable
     */
    public function getEntity()
    {
        return $this->modelEntity;
    }
}
