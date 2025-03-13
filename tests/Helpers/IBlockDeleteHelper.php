<?php

namespace Pago\Bitrix\Tests\Helpers;

use Bitrix\Iblock\PropertyTable;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;

/**
 * Тестирование удаления моделей инфоблока
 */
final class IBlockDeleteHelper
{
    /**
     * Метод для удаления инфоблока с зависимостями
     * @param $iblockId
     * @return void
     * @throws \Exception
     */
    public static function deleteIblockWithDependencies($iblockId): void
    {
        // Удаление элементов инфоблока
        self::deleteIblockElements($iblockId);

        // Удаление свойств инфоблока
        self::deleteIblockProperties($iblockId);

        // Удаление инфоблока
        self::deleteIblock($iblockId);
    }

    /**
     * Метод для удаления элементов инфоблока
     * @param $iblockId
     * @return void
     * @throws \Exception
     */
    private static function deleteIblockElements($iblockId): void
    {
        $elements = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId],
            false,
            false,
            ['ID']
        );

        while ($element = $elements->Fetch()) {
            $elementId = $element['ID'];
            if (! CIBlockElement::Delete($elementId)) {
                throw new \Exception('Не удалось удалить элемент с ID ' . $elementId);
            }
        }
    }

    /**
     * Метод для удаления свойств инфоблока
     * @param $iblockId
     * @return void
     * @throws \Exception
     */
    private static function deleteIblockProperties($iblockId): void
    {
        $properties = PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $iblockId]
        ]);

        while ($property = $properties->fetch()) {
            $propertyId = $property['ID'];
            $iblockProperty = new CIBlockProperty();
            if (! $iblockProperty->Delete($propertyId)) {
                throw new \Exception('Не удалось удалить свойство с ID ' . $propertyId);
            }
        }
    }

    /**
     * Метод для удаления инфоблока
     * @param $iblockId
     * @return void
     * @throws \Exception
     */
    private static function deleteIblock($iblockId): void
    {
        $iblock = new CIBlock();
        if (! $iblock->Delete($iblockId)) {
            throw new \Exception('Не удалось удалить инфоблок с ID ' . $iblockId);
        }
    }
}