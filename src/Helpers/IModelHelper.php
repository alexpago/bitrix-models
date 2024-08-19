<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\SystemException;

/**
 * Статические методы для взаимодействия с моделями инфоблоков
 */
final class IModelHelper
{
    /**
     * @var array
     */
    private static array $blocks = [];

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
     * Идентификатор инфоблока по символьному коду
     * @param string $code
     * @return int
     */
    public static function getIblockIdByCode(string $code): int
    {
        if (! self::$blocks) {
            $blocks = IblockTable::query()
                ->setSelect([
                    'ID',
                    'CODE',
                    'API_CODE',
                ])
                ->fetchAll();

            array_map(function (array $value) {
                self::$blocks[] = $value;
            }, $blocks);
        }
        $search = array_search(
            $code,
            array_column(self::$blocks, 'CODE')
        );
        if (false === $search) {
            throw new SystemException(sprintf('Инфоблок с кодом %s не найден', $code));
        }
        $block = self::$blocks[$search];
        if (null === $block['API_CODE']) {
            throw new SystemException(sprintf('API_CODE инфоблока %s не указан. Заполните API_CODE', $code));
        }

        return (int)$block['ID'];
    }
}
