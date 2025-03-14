<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Pago\Bitrix\Models\Helpers\DynamicTable;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\TableModelHelper;
use Pago\Bitrix\Models\Interfaces\HighloadTableInterface;
use Pago\Bitrix\Models\Interfaces\ModelInterface;
use Pago\Bitrix\Models\Interfaces\QueryableInterface;
use Pago\Bitrix\Models\Queries\Builder;
use Pago\Bitrix\Models\Queries\TableModelQuery;
use Pago\Bitrix\Models\Traits\ModelBaseTrait;
use Pago\Bitrix\Models\Traits\ModelWhereTrait;

/**
 * Модель таблицы
 */
abstract class TableModel extends BaseModel implements ModelInterface, HighloadTableInterface
{
    use ModelBaseTrait;
    use ModelWhereTrait;

    // Переопределение названия таблицы
    public const TABLE_NAME = null;

    /**
     * @var EntityObject|null
     */
    public EntityObject|null $modelElement = null;

    /**
     * Название таблицы
     * @return string
     */
    public static function getTableName(): string
    {
        // Определение таблицы по имени
        if (static::TABLE_NAME) {
            return static::TABLE_NAME;
        }
        // Определение таблицы по классу
        $class = explode('\\', get_called_class());
        return Helper::camelToSnakeCase((string)end($class));
    }

    /**
     * Свойства таблицы
     * @return array
     */
    public static function getMap(): array
    {
        $helper = new TableModelHelper();
        return $helper->getMap(static::getTableName());
    }

    /**
     * Экземпляр объекта таблицы
     * @return DynamicTable|null
     */
    final public static function getEntity(): ?DynamicTable
    {
        $entity = new DynamicTable();
        $entity::$tableName = static::getTableName();
        $entity::$map = static::getMap();

        return $entity;
    }

    /**
     * @param Builder $queryBuilder
     * @return QueryableInterface
     */
    static protected function getQuery(Builder $queryBuilder): QueryableInterface
    {
        return new TableModelQuery(static::class, $queryBuilder);
    }

    /**
     * TODO: Перенести в BaseModel
     * @return EntityObject|null
     */
    public function element(): EntityObject|null
    {
        return $this->modelElement;
    }
}
