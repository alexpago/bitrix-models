<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Main\DB\Exception;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Helpers\DynamicTable;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\Interfaces\QueryableInterface;
use Pago\Bitrix\Models\Queries\Builder;

/**
 * Базовый класс моделей
 */
#[\AllowDynamicProperties]
abstract class BaseModel
{
    /**
     * Свойства модели
     * @var array
     */
    protected array $properties = [];

    /**
     * Свойства при инициализации модели
     * @var array
     */
    protected array $originalProperties = [];

    /**
     * Primary столбец
     * @var string
     */
    public string $primary = 'ID';

    /**
     * @var Builder|null
     */
    public ?Builder $builder = null;

    /**
     * Получение экземпляра класса
     * @return CommonElementTable|DataManager|DynamicTable|null
     */
    abstract static function getEntity(): CommonElementTable|DataManager|DynamicTable|null;

    /**
     * @return mixed
     */
    abstract public function element(): mixed;

    /**
     * @param Builder $queryBuilder
     * @return QueryableInterface
     */
    abstract static protected function getQuery(Builder $queryBuilder): QueryableInterface;

    /**
     * Инициализация запроса
     * @return Builder
     */
    final public static function query(): Builder
    {
        return new Builder(new static());
    }

    /**
     * Добавление элементов
     * @param array $data
     * @return Result[]
     */
    final public static function insert(array $data): array
    {
        if (!array_is_list($data)) {
            $data = [$data];
        }
        $result = [];
        foreach ($data as $item) {
            $model = new static();
            $model->fill($item);
            $result[] = $model->save();
        }

        return $result;
    }

    /**
     * Добавление элемента
     * @param array $data
     * @return Result
     */
    final public static function add(array $data): Result
    {
        $model = new static();
        $model->fill($data);
        return $model->save();
    }

    /**
     * Фасет GetList
     * @param array $parameters
     * @return QueryResult
     */
    final public static function getList(array $parameters = []): QueryResult
    {
        return static::getEntity()::getList($parameters);
    }

    /**
     * Результат запроса в виде массива
     * @param Builder $builder
     * @return array<static>
     */
    public static function getArray(Builder $builder): array
    {
        $result = [];
        foreach (static::get($builder) as $element) {
            $result[] = $element->toArray();
        }
        return $result;
    }

    /**
     * Элементы данных
     * @param Builder $queryBuilder
     * @return array<static>
     */
    public static function get(Builder $queryBuilder): array
    {
        return static::getQuery($queryBuilder)->fetch();
    }

    /**
     * Количество элементов
     * @param Builder|null $queryBuilder
     * @return int
     */
    public static function count(Builder|null $queryBuilder = null): int
    {
        if (! $queryBuilder) {
            $queryBuilder = new Builder(new static());
        }
        return static::getQuery($queryBuilder)->count();
    }

    /**
     * @param BaseModel $model
     * @param EntityObject $element
     * @param Builder $builder
     * @return $this
     */
    public static function setElement(
        BaseModel    $model,
        EntityObject $element,
        Builder      $builder
    ): static
    {
        $model->builder = $builder;
        $model->modelElement = $element;
        try {
            $model->originalProperties = $element->collectValues();
            $model->fill($model->originalProperties);
        } catch (SystemException) {
        }
        return $model;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function fill(array $data): static
    {
        foreach ($data as $parameter => $value) {
            $this->properties[$parameter] = $value;
        }
        return $this;
    }

    /**
     * Актуализировать элемент из БД
     * @return $this
     */
    public function refresh(): static
    {
        // Элемент привязываем к ID, если такового нет, не обновляем
        if (! $this->{$this->primary}) {
            return $this;
        }
        $element = $this::query()
            ->withProperties()
            ->where($this->primary, '=', $this->{$this->primary})
            ->first();
        if ($element) {
            $this->fill($element->toArray());
        }
        return $this;
    }

    /**
     * @param string $property
     * @param $value
     * @return void
     */
    public function __set(string $property, $value): void
    {
        $this->properties[$property] = $value;
    }

    /**
     * @param string $property
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        // Построительный нового builder
        $builder = new Builder(new static());
        if (method_exists($builder, $name)) {
            return $builder->$name(...$arguments);
        }
        return null;
    }

    /**
     * Получить primary ключ
     * @return string|null
     */
    public function getPrimaryKey(): ?string
    {
        return $this->primary;
    }

    /**
     * Получить primary - значение
     * @return string|int|null
     */
    public function getPrimary(): string|int|null
    {
        return $this->getPrimaryKey() ? ($this->{$this->getPrimaryKey()} ?? null) : null;
    }

    /**
     * Получить primary - значение (аналог getPrimary)
     * @return string|int|null
     */
    public function getPrimaryValue(): string|int|null
    {
        return $this->getPrimary();
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return null !== $this->element();
    }

    /**
     * Свойство по ключу
     * @param string $property
     * @return mixed
     */
    public function getProperty(string $property): mixed
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * Свойства модели
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
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
        $isIblockModel = $this instanceof IModel;
        $iblockId = $isIblockModel ? $this::iblockId() : 0;

        /**
         * Обновление текущего элемента
         */
        if ($this->element()) {
            if ($callEvents) {
                $this->onBeforeUpdate();
            }
            // Свойства инфоблока запишем и сохраним отдельно
            $iblockProperties = [];
            foreach ($data as $property => $value) {
                // Инфоблок обработаем иначе
                if ($isIblockModel) {
                    // Базовое поле сохраним обычно
                    if (IModelHelper::isBaseField($property)) {
                        $this->element()->set($property, $value);
                    } elseif (IModelHelper::isProperty($iblockId, $property)) {
                        // Свойство инфоблока
                        $iblockProperties[$property] = $value;
                    }
                    continue;
                }
                // Обычное поле справочника или таблицы
                $this->element()->set($property, $value);
            }
            // Сохраним свойства инфоблока
            if ($iblockProperties) {
                \CIBlockElement::SetPropertyValuesEx($this->ID, false, $iblockProperties);
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
         * Добавление нового элемента
         * @var CommonElementTable|DataManager $entity
         */
        if ($callEvents) {
            $this->onBeforeAdd();
        }
        try {
            $addData = $data;
            // Для инфоблока добавим сначала поля инфоблока, потом свойства
            if ($isIblockModel) {
                $addData = array_intersect_key($addData, array_flip(IModel::getBaseFields()));
            }
            $result = $this::getEntity()::add($addData);
            // Для инфоблока нужно добавить свойства повторно
            if ($isIblockModel && $result->isSuccess()) {
                $element = $this::query()
                    ->where($this->getPrimaryKey(), $result->getId())
                    ->withProperties()
                    ->first();
                $element->fill(array_diff($data, $addData))->save(false);
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
     * Обновление текущего элемента. Старый метод
     * @param array $data
     * @return bool
     * @deprecated
     */
    public function elementUpdate(array $data): bool
    {
        return $this->update($data)->isSuccess();
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
        return $this->fill($data)->save($callEvents);
    }

    /**
     * Сохранение элемента и его получение
     * @return $this
     */
    public function put(bool $callEvents = true): static
    {
        $save = $this->save($callEvents);
        if (! $save->isSuccess()) {
            return $this;
        }
        $primary = $this->getProperty($this->primary) ?: $save->getId();
        $this->{$this->primary} = $primary;

        return $this->refresh();
    }

    /**
     * Удаление элемента модели
     * @param bool $callEvents
     * @return Result
     */
    public function delete(bool $callEvents = true): Result
    {
        if (! $this->exists()) {
            return new Result();
        }
        if ($callEvents) {
            $this->onBeforeDelete();
        }
        try {
            $result = $this->element()->delete();
        } catch (\Exception $e) {
            $result = new Result();
            $result->addError(Error::createFromThrowable($e));
        }
        if ($callEvents) {
            $this->onAfterDelete($result);
        }
        return $result;
    }

    /**
     * Удаление текущего элемента. Старый метод
     * @return bool
     * @deprecated
     */
    public function elementDelete(): bool
    {
        return $this->delete()->isSuccess();
    }


    /**
     * Преобразование ответа в массив
     * @return array|null
     */
    public function toArray(): ?array
    {
        return $this->getProperties();
    }

    /**
     * Свойства модели при ее инициализации
     * @return array
     */
    public function getOriginalProperties(): array
    {
        return $this->originalProperties;
    }

    /**
     * Получить статические свойства
     * @return array
     */
    public function getUnmodifiable(): array
    {
        return [];
    }

    /**
     * Измененные свойства
     * @return array
     */
    public function getChangedProperties(): array
    {
        $properties = [];
        foreach ($this->properties as $property => $value) {
            if (!isset($this->originalProperties[$property])) {
                $properties[$property] = $value;
                continue;
            }
            if ($this->originalProperties[$property] !== $value) {
                $properties[$property] = $value;
            }
        }
        return array_diff_key(
            $properties,
            array_flip($this->getUnmodifiable())
        );
    }

    /**
     * Событие вызываемое перед добавлением элементам
     * @return void
     */
    protected function onBeforeAdd(): void
    {
        // actions
    }

    /**
     * Событие вызываемое после добавление элемента
     * @param Result $result
     * @return void
     */
    protected function onAfterAdd(Result $result): void
    {
        // actions
    }

    /**
     * Событие вызываемое перед обновлением элемента
     * @return void
     */
    protected function onBeforeUpdate(): void
    {
        // actions
    }

    /**
     * Событие вызываемое после обновления элемента
     * @param Result $result
     * @return void
     */
    protected function onAfterUpdate(Result $result): void
    {
        // actions
    }

    /**
     * Событие вызываемое перед удалением элемента
     * @return void
     */
    protected function onBeforeDelete(): void
    {
        // actions
    }

    /**
     * Событие вызываемое после удаления элемента
     * @param Result $result
     * @return void
     */
    protected function onAfterDelete(Result $result): void
    {
        // actions
    }
}
