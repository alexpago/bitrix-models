<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserFieldLangTable;
use Bitrix\Main\UserFieldTable;

/**
 * Статические методы для взаимодействия с моделями инфоблоков
 */
final class HlModelHelper
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
     * Символьные кода свойств highload блока
     * @param  int  $hlId
     * @return array
     */
    public static function getPropertyCodes(int $hlId): array
    {
        return array_column(self::getProperties($hlId), 'FIELD_NAME');
    }

    /**
     * Свойства highload блока
     * @param int $hlId
     * @return array
     */
    public static function getProperties(int $hlId): array
    {
        if (! array_key_exists($hlId, self::$properties)) {
            $properties = UserFieldTable::query()
                ->setSelect([
                    'ID',
                    'CODE' => 'FIELD_NAME',
                    'USER_TYPE_ID',
                    'MULTIPLE',
                    'NAME' => 'LANG.EDIT_FORM_LABEL'
                ])
                ->setOrder([
                    'SORT' => 'ASC',
                    'ID'   => 'DESC'
                ])
                ->setFilter([
                    '=ENTITY_ID' => 'HLBLOCK_' . $hlId,
                    '=LANG.LANGUAGE_ID' => 'ru'
                ])
                ->registerRuntimeField(
                    'LANG',
                    new ReferenceField(
                        'LANG',
                        UserFieldLangTable::class,
                        [
                            '=this.ID' => 'ref.USER_FIELD_ID'
                        ]
                    )
                )
                ->fetchAll();
            if (! is_array($properties)) {
                $properties = [];
            }
            self::$properties[$hlId] = $properties;
        }

        return self::$properties[$hlId];
    }

    /**
     * Идентификатор highload блока по символьному коду
     * @param string $code
     * @return int
     */
    public static function getHlIdByCode(string $code): int
    {
        if (! self::$blocks) {
            $blocks = HighloadBlockTable::query()
                ->setSelect([
                    'ID',
                    'NAME',
                    'CODE' => 'NAME',
                    'TABLE_NAME'
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
            throw new SystemException(sprintf('Highload блок с кодом %s не найден', $code));
        }

        return (int)self::$blocks[$search]['ID'];
    }
}
