<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Helpers\DynamicTable;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\TableModelHelper;
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
     * @param int|null $limit
     * @param int|null $offset
     * @return array|static[]
     */
    public function get(?int $limit = null, ?int $offset = null): array
    {
        if (null !== $limit) {
            $this->setLimit($limit);
        }
        if (null !== $offset) {
            $this->setOffset($offset);
        }

        return TableModelQuery::instance(static::class)->fetch(
            filter: $this->queryFilter,
            select: $this->querySelect,
            order: $this->queryOrder,
            limit: $this->queryLimit,
            offset: $this->queryOffset,
            cacheTtl: $this->cacheTtl,
            cacheJoin: $this->cacheJoin
        );
    }

    /**
     * @param EntityObject $element
     * @return $this
     */
    public function setElement(EntityObject $element): static
    {
        $this->modelElement = $element;
        try {
            $this->originalProperties = $element->collectValues();
            $this->fill($this->originalProperties);
        } catch (SystemException) {}

        return $this;
    }

    /**
     * @return EntityObject|null
     */
    public function element(): EntityObject|null
    {
        return $this->modelElement;
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
}
