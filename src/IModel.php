<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use CIBlockElement;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\DB\Exception;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Iblock\ORM\ValueStorage;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\Interfaces\ModelInterface;
use Pago\Bitrix\Models\Interfaces\QueryableInterface;
use Pago\Bitrix\Models\Queries\Builder;
use Pago\Bitrix\Models\Queries\IModelQuery;

/**
 * Модель инфоблока
 * @property int ID Идентификатор элемента
 * @property string|null TIMESTAMP_X Дата последнего изменения элемента
 * @property int MODIFIED_BY Идентификатор пользователя, который последний раз изменил элемент
 * @property string|null DATE_CREATE Дата создания элемента
 * @property int CREATED_BY Идентификатор пользователя, который создал элемент
 * @property int IBLOCK_ID Идентификатор инфоблока
 * @property int IBLOCK_SECTION_ID Идентификатор раздела инфоблока
 * @property bool ACTIVE Статус активности элемента
 * @property string|null ACTIVE_FROM Дата начала активности элемента
 * @property string|null ACTIVE_TO Дата окончания активности элемента
 * @property int SORT Позиция элемента для сортировки
 * @property string NAME Название элемента
 * @property int PREVIEW_PICTURE Идентификатор изображения-превью
 * @property string PREVIEW_TEXT Текст-превью
 * @property string PREVIEW_TEXT_TYPE Тип текста-превью (например, "text" или "html")
 * @property int DETAIL_PICTURE Идентификатор изображения для детальной страницы
 * @property string DETAIL_TEXT Текст на детальной странице
 * @property string DETAIL_TEXT_TYPE Тип текста на детальной странице (например, "text" или "html")
 * @property string SEARCHABLE_CONTENT Строка контента для поиска
 * @property int WF_STATUS_ID Статус рабочего процесса
 * @property int WF_PARENT_ELEMENT_ID Идентификатор родительского элемента рабочего процесса
 * @property string|null WF_NEW Признак нового элемента в рабочем процессе
 * @property int WF_LOCKED_BY Идентификатор пользователя, который заблокировал элемент
 * @property string|null WF_DATE_LOCK Дата блокировки элемента
 * @property string WF_COMMENTS Комментарии рабочего процесса
 * @property bool IN_SECTIONS Флаг нахождения в разделе
 * @property string XML_ID Внешний идентификатор элемента
 * @property string CODE Символьный код элемента
 * @property string TAGS Теги элемента
 * @property string TMP_ID Временный идентификатор
 * @property int SHOW_COUNTER Количество показов элемента
 * @property string|null SHOW_COUNTER_START Дата и время первого показа элемента
 * @property string DETAIL_PAGE_URL URL страницы элемента
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
class IModel extends BaseModel implements ModelInterface
{
    // Переопределение кода инфоблока
    public const IBLOCK_CODE = null;

    // Переопределение ID инфоблока
    public const IBLOCK_ID = null;

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
        if (!static::IBLOCK_CODE && preg_match('/iblock([0-9])+/i', $class)) {
            return Helper::getOnlyNumeric($class);
        }
        // По символьному коду
        if (static::IBLOCK_CODE) {
            $code = (string)static::IBLOCK_CODE;
        } else {
            $code = Helper::camelToSnakeCase($class);
        }
        $iblock = IModelHelper::getIblockDataByCode($code);
        if (! $iblock) {
            throw new SystemException(sprintf('Инфоблок с кодом %s не найден', $code));
        }
        if (null === $iblock['API_CODE']) {
            throw new SystemException(sprintf('API_CODE инфоблока %s не указан. Заполните API_CODE', $code));
        }
        return (int)$iblock['ID'];
    }

    /**
     * Экземпляр объекта инфоблока
     * @return CommonElementTable
     * @throws SystemException
     */
    final public static function getEntity(): CommonElementTable
    {
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
     * Чтение свойств
     * @param string $property
     * @return mixed
     */
    final public function __get(string $property): mixed
    {
        if (null === $this->element()) {
            return null;
        }
        $value = $this->toArray()[$property];
        // Возможно это свойство
        if (in_array($property, IModelHelper::getIblockPropertyCodes($this::iblockId()))) {
            // Базовые свойства возвращают ~VALUE, VALUE
            // Кастомные свойства могут возвращать любые данные
            return $value['~VALUE'] ?? $value['VALUE'] ?? $value ?? null;
        }
        return $value ?: null;
    }

    /**
     * Установка элемента модели
     * @param IModel $model
     * @param EntityObject $element
     * @param Builder $builder
     * @return $this
     */
    final public static function setElement(
        BaseModel    $model,
        EntityObject $element,
        Builder      $builder
    ): static
    {
        /**
         * @var ElementV2|ElementV1 $element
         */
        $model->builder = $builder;
        $model->modelElement = $element;
        $model->originalProperties
            = $model->properties
            = $model->toArray();
        return $model;
    }


    /**
     * @param Builder $queryBuilder
     * @return QueryableInterface
     */
    static protected function getQuery(Builder $queryBuilder): QueryableInterface
    {
        return new IModelQuery(static::class, $queryBuilder);
    }

    /**
     * Обновление/сохранение элементов
     * @param bool $callEvents
     * @return Result
     */
    public function save(bool $callEvents = true): Result
    {
        $data = $this->getChangedProperties();
        if (! $data) {
            return new Result();
        }
        // Сценарий: Обновление текущего элемента
        if ($this->exists()) {
            return $this->update($this->getChangedProperties(), $callEvents);
        }
        /**
         * Сценарий: Добавление нового элемента
         * @var CommonElementTable|DataManager $entity
         */
        if ($callEvents) {
            $this->onBeforeAdd();
        }
        try {
            // Шаг 1: Сохраним элемент с базовыми полями инфоблока
            $baseFields = array_intersect_key($data, array_flip(IModelHelper::getBaseFields()));
            $result = $this::getEntity()::add($baseFields);
            // Шаг 2: Сохраним повторно только свойства
            if ($result->isSuccess()) {
                $element = $this::query()
                    ->where($this->getPrimaryKey(), $result->getId())
                    ->withProperties()
                    ->first();
                $element->fill(array_diff($data, $baseFields))->save(false);
            }
        } catch (Exception $e) {
            $result = new Result();
            $result->setData($data);
            $result->addError(Error::createFromThrowable($e));
        }
        if ($callEvents) {
            $this->onAfterAdd($result);
        }
        return $result;
    }

    /**
     * Обновление элемента модели
     * @param array $data
     * @param bool $callEvents
     * @return Result
     */
    public function update(array $data, bool $callEvents = true): Result
    {
        if (! $this->exists()) {
            return new Result();
        }
        if ($callEvents) {
            $this->onBeforeUpdate();
        }
        // Свойства инфоблока запишем и сохраним отдельно
        $iblockProperties = [];
        foreach ($data as $property => $value) {
            // Базовое поле сохраним обычно
            if (IModelHelper::isBaseField($property)) {
                $this->element()->set($property, $value);
            } elseif (IModelHelper::isProperty($this->getIblockId(), $property)) {
                // Свойство инфоблока
                $iblockProperties[$property] = $value;
            }
        }
        // Сохраним свойства инфоблока
        if ($iblockProperties) {
            CIBlockElement::SetPropertyValuesEx($this->ID, $this->getIblockId(), $iblockProperties);
        }
        try {
            $update = $this->element()->save();
        } catch (Exception $e) {
            $update = new Result();
            $update->setData($data);
            $update->addError(Error::createFromThrowable($e));
        }
        if ($callEvents) {
            $this->onAfterUpdate($update);
        }
        return $update;
    }

    /**
     * Вызов методов объекта ElementV1|ElementV1
     * @param string $name
     * @param array $arguments
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
        return parent::__call($name, $arguments);
    }

    /**
     * Получить ID инфоблока
     * @return int
     */
    public function getIblockId(): int
    {
        return $this->IBLOCK_ID ?: $this::iblockId();
    }

    /**
     * Детальная ссылка на элемент
     * @return string|null
     */
    public function getDetailPageUrl(): ?string
    {
        if (! $this->exists() || ! $this->builder) {
            return null;
        }
        if (null === $this->detailPageUrl) {
            $this->detailPageUrl = IModelHelper::getDetailPageUrl($this->ID, $this->getIblockId())[$this->ID] ?? null;
        }
        return $this->detailPageUrl;
    }

    /**
     * TODO: Перенести в BaseModel
     * Элемент модели
     * @return ElementV2|ElementV1|null
     */
    public function element(): ElementV2|ElementV1|null
    {
        return $this->modelElement;
    }

    /**
     * Преобразование ответа в массив
     * @return array|null
     */
    public function toArray(): ?array
    {
        if (! $this->element()) {
            return null;
        }
        return array_map(function ($value) {
            return $this->getToArrayValue($value);
        }, $this->element()->collectValues() + $this->getProperties());
    }

    /**
     * Неизменяемые свойства
     * @return string[]
     */
    public function getUnmodifiable(): array
    {
        return [
            'DETAIL_PAGE_URL'
        ];
    }

    /**
     * Преобразование значения из объектов в массив
     * @param mixed $value
     * @return mixed
     */
    private function getToArrayValue(mixed $value): mixed
    {
        // Одно значение из ElementV1/ElementV2
        if ($value instanceof ValueStorage) {
            try {
                $collectValues = $value->collectValues();
            } catch (ArgumentException) {
                $collectValues = null;
            }
            if (is_array($collectValues) && array_key_exists('VALUE', $collectValues)) {
                $collectValues = $collectValues['VALUE'];
            }
            return $collectValues;
        }

        // Несколько значение из ElementV1/ElementV2
        if ($value instanceof Collection) {
            $result = [];
            foreach ($value as $collectValues) {
                $result[] = $this->getToArrayValue($collectValues);
            }
            return $result;
        }

        // DateTime
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        return $value;
    }
}
