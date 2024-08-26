<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Data\ElementResult;
use Pago\Bitrix\Models\Helpers\Helper;
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
     * Чтение свойств из @see $element
     * @param string $parameter
     * @return mixed
     */
    public function __get(string $parameter): mixed
    {
        if (null === $this->element()) {
            return null;
        }
        if (! property_exists($this, $parameter)) {
            return null;
        }

        return $this->{$parameter};
    }

    /**
     * Вызов методов объекта ElementV1|ElementV1
     * @param  string  $name
     * @param  array  $arguments
     * @return null
     * @throws SystemException
     * @see ElementV2
     * @see ElementV1
     */
    public function __call(string $name, array $arguments)
    {
        // Построитель поиска
        if (preg_match('/where([a-z])/i', $name)) {
            $field = strtoupper(Helper::camelToSnakeCase(str_replace('where', '', $name)));
            $operator = $arguments[1] ?? '=';

            return $this->where(
                $field,
                $operator,
                $arguments[0]
            );
        }

        return null;
    }

    /**
     * Фильтрация свойства по null
     * @param string $where
     * @return $this
     */
    final public function whereNull(string $where): static
    {
        $this->queryFilter['=' . $where] = null;

        return $this;
    }

    /**
     * Фильтрация свойства по not null
     * @param string $where
     * @return $this
     */
    final public function whereNotNull(string $where): static
    {
        $this->queryFilter['!' . $where] = null;

        return $this;
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
     * Удаление текущего элемента или элементов запроса
     * @return array<ElementResult>
     */
    public function delete(): array
    {
        $result = [];
        $elements = [];
        if (null !== $this->element()) {
            $elements[] = $this->element();
        }
        if (! $elements && $this->queryIsInit) {
            if (! $this->queryFilter) {
                $result[] = new ElementResult(
                    elementId: 0,
                    success: false,
                    error: 'Нельзя удалить объекты без фильтра. Установить фильтр'
                );

                return $result;
            }
            $elements = $this->get();
        }
        if (! $elements) {
            return $result;
        }

        foreach ($elements as $element) {
            /**
             * @var EntityObject $element
             * @var Result $delete
             */
            $delete = $element->delete();
            $result[] = new ElementResult(
                elementId: (int)$element->getId(),
                success: $delete->isSuccess(),
                error: $delete->getErrorMessages()
            );
        }

        return $result;
    }

    /**
     * Удаление текущего элемента
     * @return bool
     */
    public function elementDelete(): bool
    {
        if (! $this->element()) {
            return false;
        }
        /**
         * @var ElementResult $delete
         */
        $delete = current($this->delete());

        return $delete->success;
    }

    /**
     * @return $this|null
     */
    public function first(): ?static
    {
        $elements = $this->get(1);

        return $elements ? $elements[0] : null;
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
