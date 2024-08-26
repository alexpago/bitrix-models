<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Iblock\ORM\ValueStorage;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Data\ElementResult;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\Queries\IModelQuery;

/**
 * Базовый класс моделей инфоблоков
 */
abstract class BaseIModel extends BaseModel
{
    public const IBLOCK_CODE = null;
    public const IBLOCK_ID = null;

    /**
     * Ссылка на детальную страницу
     * @var string|null
     */
    public ?string $detailPageUrl = null;

    /**
     * @var bool
     */
    private bool $withProperties = false;

    /**
     * @var bool
     */
    private bool $withDetailPageUrl = false;

    /**
     * @var ElementV2|ElementV1|null
     */
    public ElementV2|ElementV1|null $modelElement = null;

    /**
     * Кэш в секундах
     * @var int
     */
    public int $cacheTtl = 0;

    /**
     * Вычисление идентификатора инфоблока
     * @return int
     * @throws SystemException
     */
    final public static function iblockId(): int
    {
        if (null !== static::IBLOCK_ID) {
            return (int)static::IBLOCK_ID;
        }
        $class = explode('\\', static::class);
        $class = end($class);
        // Определение id из названия класса
        if (! static::IBLOCK_CODE && preg_match('/iblock([0-9])+/i', $class)) {
            return (int)Helper::getOnlyNumeric($class);
        }
        // По символьному коду
        if (static::IBLOCK_CODE) {
            return IModelHelper::getIblockIdByCode((string)static::IBLOCK_CODE);
        }

        return IModelHelper::getIblockIdByCode(Helper::camelToSnakeCase($class));
    }

    /**
     * Фасет GetList
     * @param array $parameters
     * @return QueryResult
     * @see CommonElementTable::getList()
     */
    final public static function getList(array $parameters = []): QueryResult
    {
        return IModelQuery::instance(static::class)->getList($parameters);
    }

    /**
     * Чтение свойств из @see $element
     * @param string $parameter
     * @return mixed
     */
    final public function __get(string $parameter): mixed
    {
        if (null === $this->element()) {
            return null;
        }

        return $this->toArrayOnlyValues()[Helper::snakeToCamelCase($parameter, true)] ?? null;
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
    final public function __call(string $name, array $arguments)
    {
        // Поиск getFieldName из объекта $this->element()
        if ($this->element() && preg_match('/get([a-z])/i', $name)) {
            return $this->element()->$name($arguments);
        }
        // Построитель поиска
        if (preg_match('/where([a-z])/i', $name)) {
            $field = strtoupper(Helper::camelToSnakeCase(str_replace('where', '', $name)));
            // Если это свойство, то добавим VALUE для источника поиска
            if (! in_array($field, $this->getBaseFields())) {
                $field .= '.VALUE';
            }
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
     * Добавить кэширование запроса
     * @param int $ttl Время жизни кэша в секундах
     * @return $this
     */
    final public function withCache(int $ttl): static
    {
        $this->cacheTtl = $ttl;

        return $this;
    }

    /**
     * Исключить кэширование запроса
     * @return $this
     */
    final public function withoutCache(): static
    {
        $this->cacheTtl = 0;

        return $this;
    }

    /**
     * Фильтрация свойства по null
     * @param string $where
     * @return $this
     */
    final public function whereNull(string $where): static
    {
        if (in_array($where, $this->getBaseFields())) {
            $this->queryFilter['=' . $where] = false;
        }

        call_user_func_array(
            [
                $this,
                'where' . Helper::snakeToCamelCase($where, true)
            ],
            [
                'null'
            ]
        );

        return $this;
    }

    /**
     * Фильтрация свойства по not null
     * @param string $where
     * @return $this
     */
    final public function whereNotNull(string $where): static
    {
        if (in_array($where, $this->getBaseFields())) {
            $this->queryFilter['!' . $where] = false;
        }

        call_user_func_array(
            [
                $this,
                'where' . Helper::snakeToCamelCase($where, true)
            ],
            [
                'null',
                '!='
            ]
        );

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

        return IModelQuery::instance(static::class)->fetch(
            filter: $this->queryFilter,
            select: $this->querySelect,
            order: $this->queryOrder,
            limit: $this->queryLimit,
            offset: $this->queryOffset,
            includeProperties: $this->withProperties,
            withDetailPageUrl: $this->withDetailPageUrl,
            cacheTtl: $this->cacheTtl
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

        return IModelQuery::instance(static::class)->count($this->queryFilter);
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
             * @var ElementV1|ElementV2 $element
             * @var Result $delete
             */
            if (method_exists($element, 'delete')) {
                $delete = $element->delete();
                $result[] = new ElementResult(
                    elementId: (int)$element->getId(),
                    success: $delete->isSuccess(),
                    error: $delete->getErrorMessages()
                );
            }
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
     * @return string|null
     */
    public function getDetailPageUrl(): ?string
    {
        $element = $this->element();
        if (! method_exists($element, 'getId')) {
            return null;
        }
        if (null === $this->detailPageUrl) {
            $elementId = (int)$element->getId();
            $this->detailPageUrl = IModelQuery::instance(static::class)
                ->getDetailPageUrl($elementId)[$elementId];
        }

        return $this->detailPageUrl;
    }

    /**
     * @return $this
     */
    public function withProperties(): self
    {
        $this->withProperties = true;

        return $this;
    }

    /**
     * Получить элементы с начальной загрузкой детальной страницы
     * @return $this
     */
    public function withDetailPageUrl(): self
    {
        $this->withDetailPageUrl = true;

        return $this;
    }

    /**
     * @param  ElementV2|ElementV1  $element
     * @return $this
     */
    public function setElement(ElementV2|ElementV1 $element): static
    {
        $this->modelElement = $element;

        return $this;
    }

    /**
     * @return ElementV2|ElementV1|null
     */
    public function element(): ElementV2|ElementV1|null
    {
        return $this->modelElement;
    }

    /**
     * Базовые поля инфоблока
     * @return array
     */
    protected function getBaseFields(): array
    {
        return array_keys(
            (new ElementTable())->getEntity()->getFields()
        );
    }

    /**
     * Преобразование ответа в массив без связей
     * @return array|null
     */
    public function toArrayOnlyValues(): ?array
    {
        return $this->toArray(false);
    }

    /**
     * Преобразование ответа в массив
     * @param bool $relations Включить связи (IBLOCK_ELEMENT_ID)
     * @return array|null
     */
    public function toArray(bool $relations = true): ?array
    {
        $element = $this->element();
        if (! $element || ! method_exists($element, 'collectValues')) {
            return null;
        }
        $result = [];

        foreach ($element->collectValues() as $property => $value) {
            $result[$property] = $this->getToArrayValue($value, $relations);
        }

        return $result;
    }

    /**
     * Преобразование значения из объектов в массив
     * @param mixed $collectionValue
     * @param bool $includeRelations Включить связи
     * @return mixed
     */
    private function getToArrayValue(mixed $collectionValue, bool $includeRelations): mixed
    {
        if ($collectionValue instanceof ValueStorage) {
            try {
                $value = $collectionValue->collectValues();
            } catch (ArgumentException) {
                $value = null;
            }

            if (
                ! $includeRelations
                && is_array($value)
                && array_key_exists('VALUE', $value)
            ) {
                $value = $value['VALUE'];
            }

            return $value;
        }

        if ($collectionValue instanceof Collection) {
            $result = [];
            foreach ($collectionValue as $value) {
                $result[] = $this->getToArrayValue($value, $includeRelations);
            }

            return $result;
        }

        return $collectionValue;
    }
}
