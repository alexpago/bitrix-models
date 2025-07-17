<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
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
     * Предопределение типов полей.
     * Доступно: integer, float, boolean, decimal, string, date, datetime, array, enum, object
     * @var array
     */
    public static array $casts = [];

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
        return $helper->getMap(static::getTableName(), static::$casts);
    }

    /**
     * Экземпляр объекта таблицы
     * @return DataManager|null
     * @throws ArgumentException
     * @throws SystemException
     */
    final public static function getEntity(): ?DataManager
    {
        return TableModelQuery::getModelEntity(static::class);
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
