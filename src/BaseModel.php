<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Main\DB\Exception;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\Result;
use Pago\Bitrix\Models\Helpers\DynamicTable;
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
     * Получение данных массивом
     * @return array|null
     */
    abstract function toArray(): ?array;

    /**
     * Получение экземпляра класса
     * @return CommonElementTable|DataManager|DynamicTable|null
     */
    abstract static function getEntity(): CommonElementTable|DataManager|DynamicTable|null;

    /**
     * Результат запроса
     * @param Builder $builder
     * @return array<static>
     */
    abstract static function get(Builder $builder): array;

    /**
     * Количество элементов
     * @param Builder $builder
     * @return int
     */
    abstract public static function count(Builder $builder): int;

    /**
     * @return mixed
     */
    abstract public function element(): mixed;

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
        if (! array_is_list($data)) {
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

        return $this::query()
            ->setFilter(
                [
                    '=' . $this->primary => $this->{$this->primary}
                ]
            )
            ->first();
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
     * Получить primary ключ
     * @return string|null
     */
    public function getPrimaryKey(): ?string
    {
        return $this->primary ? ($this->{$this->primary} ?? null) : null;
    }

    /**
     * Получить primary
     * @return string|int|null
     */
    public function getPrimary(): string|int|null
    {
        return $this->getPrimaryKey() ? ($this->{$this->getPrimaryKey()} ?? null) : null;
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
     * @return Result
     */
    public function save(): Result
    {
        $data = $this->getChangedProperties();
        if (! $data) {
            return new Result();
        }
        // Обновление текущего элемента
        if ($this->element()) {
            $this->onBeforeUpdate();
            array_walk($data, function ($value, $field) {
                $this->element()->set($field, $value);
            });
            try {
                $update = $this->element()->save();
            } catch (Exception $e) {
                $update = new Result();
                $update->setData($data);
                $update->addError(Error::createFromThrowable($e));
            }
            $this->onAfterUpdate($update);

            return $update;
        }
        /**
         * Добавление нового элемента
         * @var CommonElementTable|DataManager $entity
         */
        $this->onBeforeAdd();
        try {
            $result = $this::getEntity()::add($data);
        } catch (Exception $e) {
            $result = new Result();
            $result->setData($data);
            $result->addError(Error::createFromThrowable($e));
        }
        $this->onAfterAdd($result);

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
     * Обновление элемента модели или элементов запроса.
     * @param array $data
     * @return Result
     */
    public function update(array $data): Result
    {
        if (! $this->exists()) {
            return new Result();
        }
        return $this->fill($data)->save();
    }

    /**
     * Сохранение элемента и его получение
     * @return $this
     */
    public function put(): static
    {
        $save = $this->save();
        if (! $save->isSuccess()) {
            return $this;
        }
        $primary = $this->getProperty($this->primary) ?: $save->getId();
        $this->{$this->primary} = $primary;

        return $this->refresh();
    }

    /**
     * Удаление элемента модели
     * @return Result
     */
    public function delete(): Result
    {
        if (! $this->exists()) {
            return new Result();
        }
        $this->onBeforeDelete();
        try {
            $result = $this->element()->delete();
        } catch (\Exception $e) {
            $result = new Result();
            $result->addError(Error::createFromThrowable($e));
        }
        $this->onAfterDelete($result);
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
    public function getStaticProperties(): array
    {
        $properties = [];
        // DETAIL_PAGE_URL вычисляемое значение для инфоблока
        if ($this instanceof IModel) {
            $properties[] = 'DETAIL_PAGE_URL';
        }
        return $properties;
    }

    /**
     * Измененные свойства
     * @return array
     */
    public function getChangedProperties(): array
    {
        $staticProperties = $this->getStaticProperties();
        return array_diff_key(
            array_diff_assoc($this->properties, $this->originalProperties),
            array_combine($staticProperties, array_fill(0, count($staticProperties), null))
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
