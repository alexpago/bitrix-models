<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Main\ORM\Data\Result;
use Pago\Bitrix\Models\BaseModel;

/**
 * Вспомогательные методы обновления элементов
 */
trait ModelSaveTrait
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
            $update = $this->element()->save();
            $this->onAfterUpdate($update);

            return $update;
        }
        /**
         * Добавление нового элемента
         * @var CommonElementTable|DataManager $entity
         */
        $this->onBeforeAdd();
        $entity = $this::getEntity();
        $result = $entity::add($data);
        $this->onAfterAdd($result);

        return $result;
    }

    /**
     * Обновление элемента модели или элементов запроса.
     * @return array<Result>
     */
    public function update(array $data): array
    {
        $result = [];
        $elements = [];
        if (null !== $this->element()) {
            $elements[] = $this;
        }
        if (! $elements && $this->queryIsInit) {
            $elements = $this->get();
        }
        if (! $elements) {
            return $result;
        }

        foreach ($elements as $element) {
            /**
             * @var BaseModel $element
             */
            $result[] = $element->fill($data)->save();
        }

        return $result;
    }

    /**
     * Обновление текущего элемента
     * @param array $data
     * @return bool
     */
    public function elementUpdate(array $data): bool
    {
        $result = $this->element() ? $this->update($data) : null;

        return $result && $result[0]->isSuccess();
    }

    /**
     * Сохранение элемента и его получение
     * @return $this
     */
    public function put(): static
    {
        $save = $this->save();
        if (! $save->isSuccess() || ! $save->getId()) {
            return $this;
        }
        $this->{$this->primary} = $save->getId();

        return $this->refresh();
    }
}
