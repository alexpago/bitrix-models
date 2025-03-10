<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Exception;
use Pago\Bitrix\Models\Helpers\HlModelHelper;
use Pago\Bitrix\Models\Queries\Builder;
use Pago\Bitrix\Models\Queries\HlModelQuery;

/**
 * Базовый класс моделей highload блоков
 */
abstract class HlModel extends BaseModel
{
    // Переопределение ID справочника
    public const HL_ID = null;

    // Переопределение кода справочника
    public const HL_CODE = null;

    /**
     * @var EntityObject|null
     */
    public EntityObject|null $modelElement = null;

    /**
     * Сущность класса highload блока
     * @return string|null
     * @throws Exception
     */
    final public static function getEntityClass(): ?string
    {
        Loader::includeModule('highloadblock');
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
     * @param HlModel $model
     * @param EntityObject $element
     * @return $this
     */
    final public static function setElement(HlModel $model, EntityObject $element): static
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
     * Результат запроса
     * @param Builder $builder
     * @return array<static>
     */
    public static function get(Builder $builder): array
    {
        return HlModelQuery::instance(static::class)->fetch($builder);
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
        return HlModelQuery::instance(static::class)->count($builder);
    }

    /**
     * Преобразование ответа в массив
     * @return array|null
     */
    public function toArray(): ?array
    {
        try {
            return $this->modelElement->collectValues();
        } catch (SystemException) {
            return null;
        }
    }

    /**
     * @return EntityObject|null
     */
    public function element(): EntityObject|null
    {
        return $this->modelElement;
    }
}
