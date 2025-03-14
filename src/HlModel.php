<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Helpers\HlModelHelper;
use Pago\Bitrix\Models\Interfaces\HighloadTableInterface;
use Pago\Bitrix\Models\Interfaces\ModelInterface;
use Pago\Bitrix\Models\Queries\Builder;
use Pago\Bitrix\Models\Queries\HlModelQuery;
use Pago\Bitrix\Models\Interfaces\QueryableInterface;

/**
 * Модель highload блока
 */
abstract class HlModel extends BaseModel implements ModelInterface, HighloadTableInterface
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
     * @throws SystemException
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
        // Определение справочника по ID
        if (null !== static::HL_ID) {
            return (int)static::HL_ID;
        }
        // Определение справочника по символьному коду
        if (static::HL_CODE) {
            $hlId = HlModelHelper::getHlIdByCode((string)static::HL_CODE);
            if (! $hlId) {
                throw new SystemException(sprintf('Highload блок с кодом %s не найден', static::HL_CODE));
            }
            return $hlId;
        }
        // Определение справочника по классу
        $class = explode('\\', static::class);
        $class = end($class);
        $hlId = HlModelHelper::getHlIdByCode($class);
        if (! $hlId) {
            throw new SystemException(sprintf('Highload блок с кодом %s не найден', static::HL_CODE));
        }
        return $hlId;
    }

    /**
     * @param Builder $queryBuilder
     * @return QueryableInterface
     */
    static protected function getQuery(Builder $queryBuilder): QueryableInterface
    {
        return new HlModelQuery(static::class, $queryBuilder);
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
