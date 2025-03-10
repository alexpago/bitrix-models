<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Helpers\DynamicTable;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\TableModelHelper;
use Pago\Bitrix\Models\Queries\Builder;
use Pago\Bitrix\Models\Queries\TableModelQuery;
use Pago\Bitrix\Models\Traits\ModelBaseTrait;
use Pago\Bitrix\Models\Traits\ModelWhereTrait;

/**
 * Модель для таблиц
 */
abstract class TableModel extends BaseModel
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
        if (static::TABLE_NAME) {
            return static::TABLE_NAME;
        }
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
     * @param Builder $builder
     * @return array|static[]
     */
    public static function get(Builder $builder): array
    {
        return TableModelQuery::instance(static::class)->fetch($builder);
    }

    /**
     * @param EntityObject $element
     * @return $this
     */
    public static function setElement(TableModel $model, EntityObject $element): static
    {
        $model->modelElement = $element;
        try {
            $model->originalProperties = $element->collectValues();
            $model->fill($model->originalProperties);
        } catch (SystemException) {
        }
        return $model;
    }

    /**
     * Количество элементов в БД
     * @param Builder|null $builder
     * @return int
     */
    public static function count(?Builder $builder = null): int
    {
        if (! $builder) {
            $builder = new Builder(new static());
        }
        return TableModelQuery::instance(static::class)->count($builder);
    }

    /**
     * Перевод в массив
     * @return array|null
     */
    public function toArray(): ?array
    {
        return $this->getProperties();
    }

    /**
     * @return EntityObject|null
     */
    public function element(): EntityObject|null
    {
        return $this->modelElement;
    }
}
