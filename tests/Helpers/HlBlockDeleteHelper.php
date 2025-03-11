<?php

namespace Pago\Bitrix\Tests\Helpers;

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserFieldTable;
use Exception;

/**
 * Тестирование удаления моделей справочника
 */
final class HlBlockDeleteHelper
{
    /**
     * Удаляет Highload-блок, его поля и элементы.
     *
     * @param int $hlblockId ID Highload-блока
     * @return bool
     * @throws ArgumentException
     * @throws NotImplementedException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function deleteHighloadBlock(int $hlblockId): bool
    {
        // Получаем информацию о Highload-блоке
        $hlblock = HLBT::getById($hlblockId)->fetch();
        if (! $hlblock) {
            throw new Exception('Ошибка при удалении Highload-блока! Highload не найден');
        }

        // 1. Удаляем все элементы (данные) из Highload-блока
        $entity = HLBT::compileEntity($hlblock);
        $entityDataClass = $entity->getDataClass();

        // Удаление всех элементов
        $result = $entityDataClass::getList([
            'select' => ['ID'],
            'limit' => 1000,
        ]);

        while ($element = $result->fetch()) {
            $entityDataClass::delete($element['ID']);
        }

        // 2. Удаляем все пользовательские поля (свойства) для данного Highload-блока
        $userFields = UserFieldTable::getList([
            'filter' => ['ENTITY_ID' => 'HLBLOCK_' . $hlblockId]
        ]);

        while ($userField = $userFields->fetch()) {
            $entity = new \CUserTypeEntity();
            $entity->delete($userField['ID']);
        }

        // 3. Удаляем сам Highload-блок
        $hlblockDeleteResult = HLBT::delete($hlblockId);

        if ($hlblockDeleteResult->isSuccess()) {
            return true;
        } else {
            throw new Exception('Ошибка при удалении Highload-блока! ' . implode(', ', $hlblockDeleteResult->getErrorMessages()));
        }
    }
}