<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Interfaces\QueryableInterface;
use Pago\Bitrix\Models\TableModel;
use Pago\Bitrix\Models\Helpers\Helper;

/**
 * Запросы к таблицам
 */
final class TableModelQuery extends BaseQuery implements QueryableInterface
{
    /**
     * @var DataManager
     */
    protected DataManager $entity;

    /**
     * @param string $modelClass
     * @param Builder $queryBuilder
     * @throws ArgumentException
     * @throws SystemException
     */
    public function __construct(string $modelClass, Builder $queryBuilder)
    {
        $this->entity = self::getModelEntity($modelClass);
        parent::__construct($modelClass, $queryBuilder);
    }

    /**
     * @param string $modelClass
     * @return DataManager
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getModelEntity(string $modelClass): DataManager
    {
        /**
         * @var TableModel $model
         */
        $model = new $modelClass;
        // Данные для таблицы
        $tableName = $model::getTableName();
        $tableMap = $model::getMap();
        $entityName = Helper::snakeToCamelCase($tableName, true) . '_' . md5($tableName . serialize($tableMap)) . 'Table';
        // Создаем динамический entity
        if (! Entity::has($entityName)) {
            Entity::compileEntity(
                $entityName,
                $tableMap,
                [
                    'table_name' => $model::getTableName()
                ]
            );
        }
        return new $entityName;
    }

    /**
     * @return DataManager
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
