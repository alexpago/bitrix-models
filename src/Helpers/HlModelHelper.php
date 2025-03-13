<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\ReferenceField;
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
    private static array $cacheBlocks = [];

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
        $hls = self::getHls();
        $search = array_search($code, array_column($hls, 'CODE'));
        return intval($hls[$search]['ID'] ?? 0);
    }

    /**
     * Код highload блока по ID
     * @param int $hlId
     * @return string|null
     */
    public static function getHlCodeById(int $hlId): ?string
    {
        $hls = self::getHls();
        $search = array_search($hlId, array_column($hls, 'ID'));
        return false !== $search ? $hls[$search]['CODE'] : null;
    }

    /**
     * @return void
     */
    public static function clearHighloadModelCache(): void
    {
        self::$cacheBlocks = [];
    }

    /**
     * Получить справочники
     * @param bool $refreshCache
     * @return array
     */
    private static function getHls(bool $refreshCache = false): array
    {
        if (! self::$cacheBlocks || $refreshCache) {
            self::$cacheBlocks = HighloadBlockTable::query()
                ->setSelect([
                    'ID',
                    'NAME',
                    'CODE' => 'NAME',
                    'TABLE_NAME'
                ])
                ->fetchAll() ?: [];
        }
        return self::$cacheBlocks;
    }
}
