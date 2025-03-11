<?php

namespace Pago\Bitrix\Tests\Helpers;

use Bitrix\Iblock\TypeTable;
use CIBlockProperty;
use CIBlock;

/**
 * Создание тестового инфоблока
 */
final class IBlockCreatorHelper
{
    /**
     * Метод для создания инфоблока
     * @param string $name
     * @param string $code
     * @param string $type
     * @param string $apiCode
     * @return int
     * @throws \Exception
     */
    public static function createIblock(
        string $name,
        string $code,
        string $type,
        string $apiCode
    ): int
    {
        $iblock = new CIBlock();
        $iblockFields = [
            'NAME' => $name,
            'CODE' => $code,
            'IBLOCK_TYPE_ID' => $type,
            'API_CODE' => $apiCode,
            'ACTIVE' => 'Y',
            'SITE_ID' => ['s1'],
        ];

        $iblockId = $iblock->Add($iblockFields);

        if (! $iblockId) {
            throw new \Exception('Ошибка при создании инфоблока: ' . $iblock->LAST_ERROR);
        }

        return $iblockId;
    }

    /**
     * Метод для добавления свойств в инфоблок
     * @param int $iblockId
     * @param array $properties
     * @return void
     * @throws \Exception
     */
    public static function addProperties(int $iblockId, array $properties)
    {
        foreach ($properties as $property) {
            $propertyFields = [
                'IBLOCK_ID' => $iblockId,
                'NAME' => $property['NAME'],
                'ACTIVE' => 'Y',
                'SORT' => 100,
                'CODE' => $property['CODE'],
                'PROPERTY_TYPE' => $property['PROPERTY_TYPE'],
                'USER_TYPE' => $property['USER_TYPE'] ?? '',
                'MULTIPLE' => $property['MULTIPLE'] ?? 'N',
                'FILTRABLE' => $property['FILTRABLE'] ?? 'N',
            ];

            $iblockProperty = new CIBlockProperty();
            $result = $iblockProperty->Add($propertyFields);

            if (! $result) {
                throw new \Exception('Ошибка при добавлении свойства: ' . $iblockProperty->LAST_ERROR);
            }
        }
    }

    /**
     * Получить рандомный тип инфоблока
     * @return string|null
     */
    public static function getRandomType(): ?string
    {
        $data = TypeTable::query()->setSelect(['ID'])->setOrder(['SORT' => 'ASC'])->fetch();
        if (! $data) {
            return null;
        }
        return $data['ID'];
    }
}
