<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Iblock\ORM\ValueStorage;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\Queries\IModelQuery;

/**
 * Базовые свойства и методы модели инфоблока
 * @property int ID
 * @property DateTIme TIMESTAMP_X
 * @property int MODIFIED_BY
 * @property DateTime DATE_CREATE
 * @property int CREATED_BY
 * @property int IBLOCK_SECTION_ID
 * @property bool ACTIVE
 * @property DateTime ACTIVE_FROM
 * @property DateTime ACTIVE_TO
 * @property int SORT
 * @property string NAME
 * @property int PREVIEW_PICTURE
 * @property int DETAIL_PICTURE
 * @property string PREVIEW_TEXT_TYPE
 * @property string PREVIEW_TEXT
 * @property string DETAIL_TEXT_TYPE
 * @property string DETAIL_TEXT
 * @property string XML_ID
 * @property string CODE
 * @property string TAGS
 * @method int getId
 * @method DateTime getTimestampX
 * @method int getModifiedBy
 * @method DateTime getDateCreate
 * @method int getCreatedBy
 * @method int getIblockSectionId
 * @method bool getActive
 * @method DateTime getActiveFrom
 * @method DateTime getActiveTo
 * @method int getSort
 * @method string getName
 * @method int getPreviewPicture
 * @method string getPreviewText
 * @method string getPreviewTextType
 * @method string getDetailPicture
 * @method string getDetailText
 * @method string getXmlId
 * @method string getCode
 * @method string getTags
 * @method $this whereId(int|array $id)
 * @method $this whereTimestampX(DateTime|array $date, string $operator = '')
 * @method $this whereModifiedBy(int|array $id)
 * @method $this whereDateCreate(DateTime|array $date, string $operator = '')
 * @method $this whereCreatedBy(int|array $id)
 * @method $this whereIblockSectionId(int|array $id)
 * @method $this whereActive(bool $active)
 * @method $this whereActiveFrom(DateTime $date, string $operator = '')
 * @method $this whereActiveTo(DateTime $date, string $operator = '')
 * @method $this whereSort(int|array $sort)
 * @method $this whereName(string|array $name)
 * @method $this wherePreviewPicture(int|array $picture)
 * @method $this wherePreviewText(string|array $input)
 * @method $this wherePreviewTextType(string|array $input)
 * @method $this whereDetailPicture(int|array $picture)
 * @method $this whereDetailText(string|array $input)
 * @method $this whereXmlId(string|array $input)
 * @method $this whereCode(string|array $code)
 * @method $this whereTags(string|array $input)
 */
class IModel extends BaseModel
{
    public const IBLOCK_CODE = null;
    public const IBLOCK_ID = null;

    /**
     * Ссылка на детальную страницу
     * @var string|null
     */
    public ?string $detailPageUrl = null;

    /**
     * Включить свойства в query
     * @var bool
     */
    private bool $withProperties = false;

    /**
     * Прогрузить детальные страницы в query
     * @var bool
     */
    private bool $withDetailPageUrl = false;

    /**
     * Элемент модели
     * @var ElementV2|ElementV1|null
     */
    public ElementV2|ElementV1|null $modelElement = null;

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
     * @param string $property
     * @return mixed
     */
    final public function __get(string $property): mixed
    {
        if (null === $this->element()) {
            return null;
        }

        return $this->toArrayOnlyValues()[$property] ?? null;
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
     * Фильтрация свойства по null
     * @param string $where
     * @return $this
     */
    public function whereNull(string $where): static
    {
        // Вызывается метод __call
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
    public function whereNotNull(string $where): static
    {
        // Вызывается метод __call
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
     * Детальная ссылка на элемент
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
     * Включить свойства инфоблока
     * @param bool $includeProperties
     * @return $this
     */
    public function withProperties(bool $includeProperties = true): self
    {
        $this->withProperties = $includeProperties;

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
