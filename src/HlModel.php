<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Helpers\HlModelHelper;
use Pago\Bitrix\Models\Queries\HlModelQuery;

/**
 * Базовый класс моделей highload блоков
 */
abstract class HlModel extends BaseModel
{
    public const HL_ID = null;
    public const HL_CODE = null;

    /**
     * @var EntityObject|null
     */
    public EntityObject|null $modelElement = null;

    /**
     * Сущность класса highload блока
     * @return string|null
     */
    final public static function getEntityClass(): ?string
    {
        $entity = HighloadBlockTable::compileEntity(
            HighloadBlockTable::getById(self::hlId())->fetch()
        )->getDataClass();

        return is_string($entity) ? $entity : null;
    }

    /**
     * Экземпляр объекта highload блока
     * @return DataManager|null
     */
    final public static function getEntity(): ?DataManager
    {
        $entity = self::getEntityClass();
        if (null == $entity) {
            return null;
        }
        /**
         * @var DataManager $entity
         */
        $entity = new $entity();

        return $entity;
    }

    /**
     * Вычисление идентификатора highload блока
     * @return int
     * @throws SystemException
     */
    final public static function hlId(): int
    {
        if (null !== static::HL_ID) {
            return (int)static::HL_ID;
        }
        // По символьному коду
        if (static::HL_CODE) {
            return HlModelHelper::getHlIdByCode((string)static::HL_CODE);
        }

        $class = explode('\\', static::class);
        $class = end($class);

        return HlModelHelper::getHlIdByCode($class);
    }

    /**
     * Фасет GetList
     * @param array $parameters
     * @return QueryResult
     * @see DataManager::getList()
     */
    final public static function getList(array $parameters = []): QueryResult
    {
        return HlModelQuery::instance(static::class)->getList($parameters);
    }

    /**
     * Результат запроса
     * @param int|null $limit
     * @param int|null $offset
     * @return array<static>
     */
    public function get(?int $limit = null, ?int $offset = null): array
    {
        if (null !== $limit) {
            $this->setLimit($limit);
        }
        if (null !== $offset) {
            $this->setOffset($offset);
        }

        return HlModelQuery::instance(static::class)->fetch(
            filter: $this->queryFilter,
            select: $this->querySelect,
            order: $this->queryOrder,
            limit: $this->queryLimit,
            offset: $this->queryOffset
        );
    }

    /**
     * Количество элементов в БД
     * @return int
     */
    public function count(): int
    {
        if (! $this->queryIsInit) {
            return 0;
        }

        return HlModelQuery::instance(static::class)->count($this->queryFilter);
    }

    /**
     * @param EntityObject $element
     * @return $this
     */
    public function setElement(EntityObject $element): static
    {
        $this->modelElement = $element;
        try {
            $this->fill($element->collectValues());
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
     * Преобразование ответа в массив
     * @return array|null
     */
    public function toArray(): ?array
    {
        try {
            return $this->element()->collectValues();
        } catch (SystemException) {
            return null;
        }
    }
}
