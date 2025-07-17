<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Cache\CacheService;
use Pago\Bitrix\Models\IModel;
use CIBlockElement;

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
     * @param  int  $iblockId
     * @return array
     */
    public static function getIblockPropertyCodes(int $iblockId): array
    {
        return array_column(self::getIblockProperties($iblockId), 'CODE');
    }

    /**
     * Поле является базовым инфоблока
     * @param string $field
     * @return bool
     */
    public static function isBaseField(string $field): bool
    {
        return in_array(strtoupper($field), self::getBaseFields());
    }

    /**
     * Получить базовые поля инфоблока
     * @return array
     */
    public static function getBaseFields(): array
    {
        return array_keys(
            (new ElementTable())->getEntity()->getFields()
        );
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
     * Свойство инфоблока
     * @param int $iblockId
     * @param string $code
     * @return array|null
     */
    public static function getIblockProperty(int $iblockId, string $code): ?array
    {
        $properties = self::getIblockProperties($iblockId);
        $searchCodeIndex = array_search($code, array_column($properties, 'CODE'));
        if (false === $searchCodeIndex) {
            return null;
        }
        return $properties[$searchCodeIndex];
    }

    /**
     * Свойства инфоблока
     * @param int $iblockId
     * @return array
     */
    public static function getIblockProperties(int $iblockId): array
    {
        if (! array_key_exists($iblockId, self::$properties)) {
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
                    '=IBLOCK_ID' => $iblockId
                ])
                ->fetchAll();
            if (! is_array($properties)) {
                $properties = [];
            }
            self::$properties[$iblockId] = $properties;
        }
        return self::$properties[$iblockId];
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
    public static function getIblockIdByCode(string $code): ?int
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

    /**
     * Получить свойства инфоблока
     * @param IModel|int|array $elements
     * @param int $iblockId
     * @param array $codes
     * @param int $cacheTtl
     * @return array
     */
    public static function getProperties(
        IModel|int|array $elements,
        int              $iblockId,
        array            $codes = [],
        int              $cacheTtl = 0
    ): array
    {
        // Шаг 1: Соберем список идентификаторов элементов
        if (! ($elementIds = self::collectElementIds($elements))) {
            return [];
        }
        // Шаг 2: Кэширование. Проверка существования кэша
        $cacheKey = md5(serialize($elementIds)) . '-properties';
        if (null !== ($cache = CacheService::instance()->getIblockCache($iblockId, $cacheKey, $cacheTtl))) {
            return $cache;
        }
        // Шаг 3: Собираем информацию в виде массива свойств
        $result = [];
        CIBlockElement::GetPropertyValuesArray(
            result: $result,
            iblockID: $iblockId,
            filter: [
                'ID' => $elementIds
            ],
            propertyFilter: [
                'CODE' => $codes
            ]
        );
        // Шаг 4: Запишем кэш
        if ($cacheTtl) {
            CacheService::instance()->setIblockCache(
                iblockId: $iblockId,
                cacheKey: $cacheKey,
                data: $result,
                ttl: $cacheTtl
            );
        }
        return $result;
    }

    /**
     * Получение детальной страницы URL
     * @param IModel|int|array $elements
     * @param int $iblockId
     * @param int $cacheTtl
     * @return array<string>
     */
    public static function getDetailPageUrl(
        IModel|int|array $elements,
        int              $iblockId,
        int              $cacheTtl = 0
    ): array
    {
        // Шаг 1: Соберем список идентификаторов элементов
        if (! ($elementIds = self::collectElementIds($elements))) {
            return [];
        }
        // Шаг 2: Кэширование. Проверка существования кэша
        $cacheKey = md5(serialize($elements)) . '-detail-page-url';
        if (null !== ($cache = CacheService::instance()->getIblockCache($iblockId, $cacheKey, $cacheTtl))) {
            return $cache;
        }
        // Шаг 3: Собираем информацию в виде массива свойств
        $result = [];
        $elements = CIBlockElement::getList(
            arFilter: [
                '=ID' => $elementIds,
            ],
            arSelectFields: [
                'ID',
                'DETAIL_PAGE_URL'
            ]
        );
        while ($element = $elements->GetNext()) {
            $result[(int)$element['ID']] = $element['DETAIL_PAGE_URL'];
        }
        // Шаг 4: Запишем кэш
        if ($cacheTtl) {
            CacheService::instance()->setIblockCache(
                iblockId: $iblockId,
                cacheKey: $cacheKey,
                data: $result,
                ttl: $cacheTtl
            );
        }
        return $result;
    }

    /**
     * Собрать ID элементов из переданного значения
     * @param IModel|int|array $elements
     * @return array
     */
    private static function collectElementIds(IModel|int|array $elements): array
    {
        $elementIds = [];
        $elements = (array)$elements;
        if (array_is_list($elements)) {
            foreach ($elements as $element) {
                if (is_numeric($element)) {
                    $elementIds[] = (int)$element;
                } elseif ($element instanceof IModel && $element->ID) {
                    $elementIds[] = $element->ID;
                }
            }
        }
        return array_unique(array_filter($elementIds));
    }
}
