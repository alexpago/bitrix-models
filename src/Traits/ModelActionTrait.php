<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

use Bitrix\Main\ORM\Data\Result;
use Pago\Bitrix\Models\BaseModel;

/**
 * Вспомогательные методы удаления элементов
 */
trait ModelActionTrait
{
    /**
     * Удаление элемента модели или элементов запроса.
     * @return array<Result>
     */
    public function delete(): array
    {
        $result = [];
        if (! ($elements = $this->get())) {
            return $result;
        }

        foreach ($elements as $element) {
            $result[] = $element->delete();
        }

        return $result;
    }

    /**
     * Обновление элементов запроса
     * @return array<Result>
     */
    public function update(array $data): array
    {
        $result = [];
        if (! ($elements = $this->get())) {
            return $result;
        }

        foreach ($elements as $element) {
            $result[] = $element->fill($data)->save();
        }

        return $result;
    }
}
