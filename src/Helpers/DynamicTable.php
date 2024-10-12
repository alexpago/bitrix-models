<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

use Bitrix\Main\ORM\Data\DataManager;

/**
 * Динамическая таблица.
 * Так как обращение к BX таблицам происходит через DataManager,
 * текущий класс служит промежуточным обращением между фасетом TableModelQuery и DataManager
 */
class DynamicTable extends DataManager
{
    /**
     * Название модели
     * @var string
     */
    public static string $tableName = '';

    /**
     * Свойства модели
     * @var array
     */
    public static array $map = [];

    /**
     * Название таблицы
     * @return string
     */
    public static function getTableName(): string
    {
        return self::$tableName;
    }

    /**
     * Свойства модели
     * @return array
     */
    public static function getMap(): array
    {
        return self::$map;
    }
}