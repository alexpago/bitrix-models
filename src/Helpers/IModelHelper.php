<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;

/**
 * Статические методы для взаимодействия с моделями инфоблоков
 */
final class IModelHelper
{
    /**
     * @var array
     */
    private static array $cacheIBlocks = [];

    /**
     * @var array
     */
    private static array $properties = [];

    /**
     * Символьные кода свойств инфоблока
     * @param  int  $id
     * @return array
     */
    public static function getIblockPropertyCodes(int $id): array
    {
        return array_column(self::getIblockProperties($id), 'CODE');
    }

    /**
     * Поле является базовым инфоблока
     * @param string $field
     * @return bool
     */
    public static function isBaseField(string $field): bool
    {
        return in_array(strtoupper($field), array_keys(
            (new ElementTable())->getEntity()->getFields()
        ));
    }

    /**
     * Поле является свойством инфоблока
     * @param int $iblockId
     * @param string $property
     * @return bool
     */
    public static function isProperty(int $iblockId, string $property): bool
    {
        return in_array($property, self::getIblockPropertyCodes($iblockId));
    }

    /**
     * Свойства инфоблока
     * @param int $id
     * @return array
     */
    public static function getIblockProperties(int $id): array
    {
        if (! array_key_exists($id, self::$properties)) {
            $properties = PropertyTable::query()
                ->setSelect([
                    'ID',
                    'CODE',
                    'NAME',
                    'PROPERTY_TYPE',
                    'MULTIPLE'
                ])
                ->setOrder([
                    'SORT' => 'ASC',
                    'ID'   => 'DESC'
                ])
                ->setFilter([
                    '=IBLOCK_ID' => $id
                ])
                ->fetchAll();
            if (! is_array($properties)) {
                $properties = [];
            }
            self::$properties[$id] = $properties;
        }

        return self::$properties[$id];
    }

    /**
     * Получить информацию об инфоблоке по коду
     * @param string $code
     * @return array|null
     */
    public static function getIblockDataByCode(string $code): ?array
    {
        $iblocks = self::getIblocks();
        $search = array_search($code, array_column($iblocks, 'CODE'));
        if ($search === false) {
            return null;
        }
        return $iblocks[$search];
    }

    /**
     * Идентификатор инфоблока по символьному коду
     * @param string $code
     * @return int|null
     */
    public static function getIblockIdByCode(string $code,): ?int
    {
        $iblock = self::getIblockDataByCode($code);
        return $iblock ? (int)$iblock['ID'] : null;
    }

    /**
     * @return void
     */
    public static function clearIblockModelCache(): void
    {
        self::$cacheIBlocks = [];
    }

    /**
     * Загрузить список инфоблоков
     * @param bool $refreshCache
     * @return array
     */
    private static function getIblocks(bool $refreshCache = false): array
    {
        if (! self::$cacheIBlocks || $refreshCache) {
            self::$cacheIBlocks = IblockTable::query()
                ->setSelect(['*'])
                ->fetchAll();
        }
        return self::$cacheIBlocks;
    }
}
