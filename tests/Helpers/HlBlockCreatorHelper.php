<?php

namespace Pago\Bitrix\Tests\Helpers;

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Security\Random;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\SystemException;

/**
 * Создание тестового справочника
 */
final class HlBlockCreatorHelper
{
    /**
     * Метод для создания справочника
     * @param string $name
     * @param string $tableName
     * @return int
     * @throws SystemException
     */
    public static function createHighload(
        string $name,
        string $tableName
    ): int
    {
        $result = HLBT::add([
            'NAME' => $name,
            'TABLE_NAME' => $tableName,
        ]);

        if (! $result->isSuccess()) {
            throw new \Exception('Ошибка при создании справочника: ' . implode(' ', $result->getErrorMessages()));
        }

        return $result->getId();
    }

    /**
     * Метод для добавления свойств в справочник
     * @param int $hlblockId
     * @param array $fields
     * @return void
     * @throws \Exception
     */
    public static function addFields(int $hlblockId, array $fields): void
    {
        $entity = new \CUserTypeEntity();
        foreach ($fields as $property) {
            $propertyFields = [
                'ENTITY_ID' => 'HLBLOCK_' . $hlblockId, // Привязка к созданному HL-блоку
                'FIELD_NAME' => $property['FIELD_NAME'], // Имя поля
                'USER_TYPE_ID' => $property['USER_TYPE_ID'] ?? 'string', // Тип поля
                'XML_ID' => $property['XML_ID'] ?? $property['FIELD_NAME'] ?? Random::getString(10), // Уникальный ID
                'SORT' => $property['SORT'] ?? 100, // Позиция
                'MULTIPLE' => $property['MULTIPLE'] ?? 'N', // Множественное ли поле
                'MANDATORY' => $property['MANDATORY'] ?? 'N', // Обязательное ли поле
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'SHOW_IN_FORM' => 'Y',
                'IS_SEARCHABLE' => 'Y',
            ];

            if (! $entity->Add($propertyFields)) {
                throw new \Exception('Ошибка при добавлении поля: ' . $property['FIELD_NAME']);

            }
        }
    }
}
