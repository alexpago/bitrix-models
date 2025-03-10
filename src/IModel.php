<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Exception;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Iblock\ORM\ValueStorage;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\Queries\Builder;
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
 * @property string|null DETAIL_PAGE_URL
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
 * @method Builder|$this whereId(int|array $id)
 * @method Builder|$this whereTimestampX(DateTime|array $date, string $operator = '')
 * @method Builder|$this whereModifiedBy(int|array $id)
 * @method Builder|$this whereDateCreate(DateTime|array $date, string $operator = '')
 * @method Builder|$this whereCreatedBy(int|array $id)
 * @method Builder|$this whereIblockSectionId(int|array $id)
 * @method Builder|$this whereActive(bool $active)
 * @method Builder|$this whereActiveFrom(DateTime $date, string $operator = '')
 * @method Builder|$this whereActiveTo(DateTime $date, string $operator = '')
 * @method Builder|$this whereSort(int|array $sort)
 * @method Builder|$this whereName(string|array $name)
 * @method Builder|$this wherePreviewPicture(int|array $picture)
 * @method Builder|$this wherePreviewText(string|array $input)
 * @method Builder|$this wherePreviewTextType(string|array $input)
 * @method Builder|$this whereDetailPicture(int|array $picture)
 * @method Builder|$this whereDetailText(string|array $input)
 * @method Builder|$this whereXmlId(string|array $input)
 * @method Builder|$this whereCode(string|array $code)
 * @method Builder|$this whereTags(string|array $input)
 */
class IModel extends BaseModel
{
    // Переопределение кода инфоблока
    public const IBLOCK_CODE = null;

    // Переопределение ID инфоблока
    public const IBLOCK_ID = null;

    // Производить unserialize перед выдачей свойств инфоблока, если оно serialized
    public const UNSERIALIZE_PROPERTIES = true;

    /**
     * Ссылка на детальную страницу
     * @var string|null
     */
    public ?string $detailPageUrl = null;

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
            return static::IBLOCK_ID;
        }
        $class = explode('\\', static::class);
        $class = end($class);
        // Определение id из названия класса
        if (! static::IBLOCK_CODE && preg_match('/iblock([0-9])+/i', $class)) {
            return Helper::getOnlyNumeric($class);
        }
        // По символьному коду
        if (static::IBLOCK_CODE) {
            return IModelHelper::getIblockIdByCode((string)static::IBLOCK_CODE);
        }

        return IModelHelper::getIblockIdByCode(Helper::camelToSnakeCase($class));
    }

    /**
     * Экземпляр объекта инфоблока
     * @return CommonElementTable
     * @throws Exception
     */
    final public static function getEntity(): CommonElementTable
    {
        Loader::includeModule('iblock');
        $entity = Iblock::wakeUp(static::iblockId())->getEntityDataClass();
        if (null === $entity) {
            throw new SystemException(
                sprintf(
                    'Ошибка инициализации инфоблока ID = %d. Заполните API_CODE инфоблока',
                    static::iblockId()
                )
            );
        }
        return new $entity();
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
     * Результат запроса
     * @param Builder $builder
     * @return array<static>
     */
    public static function get(Builder $builder): array
    {
        return IModelQuery::instance(static::class)->fetch($builder);
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
     * Количество элементов в БД
     * @param Builder|null $builder
     * @return int
     */
    final public static function count(?Builder $builder = null): int
    {
        if (! $builder) {
            $builder = new Builder(new static());
        }
        return IModelQuery::instance(static::class)->count($builder);
    }

    /**
     * Установка элемента модели
     * @param IModel $model
     * @param ElementV2|ElementV1 $element
     * @param Builder $builder
     * @return $this
     */
    final public static function setElement(IModel $model, ElementV2|ElementV1 $element, Builder $builder): IModel
    {
        $model->builder = $builder;
        $model->modelElement = $element;
        $model->originalProperties = $model->properties = $model->toArray();
        return $model;
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
        return null;
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
     * Элемент модели
     * @return ElementV2|ElementV1|null
     */
    public function element(): ElementV2|ElementV1|null
    {
        return $this->modelElement;
    }

    /**
     * Преобразование ответа в массив без связей
     * @return array|null
     */
    public function toArrayOnlyValues(): ?array
    {
        return $this->toArray();
    }

    /**
     * Преобразование ответа в массив
     * @param bool $relations
     * @return array|null
     */
    public function toArray(bool $relations = false): ?array
    {
        $element = $this->element();
        if (! $element || ! method_exists($element, 'collectValues')) {
            return null;
        }

        return array_map(function ($value) use ($relations) {
            return $this->getToArrayValue($value, $relations);
        }, $element->collectValues());
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

            // Проверка на serialize объект
            if ($this::UNSERIALIZE_PROPERTIES && is_string($value)) {
                $serialize = unserialize($value);
                if (false !== $serialize) {
                    return $serialize;
                }
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

        // DateTime
        if ($collectionValue instanceof DateTime) {
            return $collectionValue->format('Y-m-d H:i:s');
        }

        return $collectionValue;
    }
}
