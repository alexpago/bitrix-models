<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

use Bitrix\Main\ORM\Data\Result;

/**
 * Вспомогательные методы удаления элементов
 */
trait ModelDeleteTrait
{
    /**
     * Удаление элемента модели или элементов запроса.
     * @return array<Result>
     */
    public function delete(): array
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
            $result[] = $element->element()->delete();
        }

        return $result;
    }

    /**
     * Удаление текущего элемента
     * @return bool
     */
    public function elementDelete(): bool
    {
        $result = $this->element() ? $this->delete() : null;

        return $result && $result[0]->isSuccess();
    }
}
