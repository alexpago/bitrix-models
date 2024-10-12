<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

use Pago\Bitrix\Models\Models\BIblockPropertyEnum;
use Pago\Bitrix\Models\Models\BUserFieldEnum;

/**
 * Вспомогательные методы связей
 */
trait ModelRelationTrait
{
    /**
     * Получить связь UserFieldEnum
     * @param int $id
     * @return BUserFieldEnum|null
     */
    public function getUserFieldEnum(int $id): ?BUserFieldEnum
    {
        return BUserFieldEnum::query()->whereId($id)->first();
    }

    /**
     * Получить связь IblockPropertyEnum
     * @param int $id
     * @return BUserFieldEnum|null
     */
    public function getIblockPropertyEnum(int $id): ?BIblockPropertyEnum
    {
        return BIblockPropertyEnum::query()->whereId($id)->first();
    }

    /**
     * Укороченный вариант метода @see self::getIblockPropertyEnum
     * @param int $id
     * @return BIblockPropertyEnum|null
     */
    public function getPropertyEnum(int $id): ?BIblockPropertyEnum
    {
        return $this->getIblockPropertyEnum($id);
    }
}
