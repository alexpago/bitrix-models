<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Main\ORM\Data\Result;

/**
 * Вспомогательные методы обновления элементов
 */
trait ModelUpdateTrait
{
    /**
     * Свойства модели
     * @var array
     */
    public array $properties = [];

    /**
     * Свойства при инициализации модели
     * @var array
     */
    public array $originalProperties = [];

    /**
     * Обновление/сохранение элементов
     * @return Result
     */
    public function save(): Result
    {
        $data = array_diff_assoc($this->properties, $this->originalProperties);
        if (! $data) {
            return new Result();
        }
        // Обновление текущего элемента
        if ($this->element()) {
            array_walk($data, function ($value, $field) {
                $this->element()->set($field, $value);
            });

            return $this->element()->save();
        }
        /**
         * Добавление нового элемента
         * @var CommonElementTable|DataManager $entity
         */
        $entity = $this::getEntity();

        return $entity::add($data);
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
            $elements[] = $this->element();
        }
        if (! $elements && $this->queryIsInit) {
            $elements = $this->get();
        }
        if (! $elements) {
            return $result;
        }

        foreach ($elements as $element) {
            foreach ($data as $field => $value) {
                $element->element()->set($field, $value);
            }
            $result[] = $element->element()->save();
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
}
