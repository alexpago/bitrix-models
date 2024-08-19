<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

use Bitrix\Main\Loader;

/**
 * Вспомогательные статические методы
 */
final readonly class Helper
{
    /**
     * camelSpace to snake_case
     * @param  string  $input
     * @return string
     */
    public static function camelToSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    /**
     * snake_case to camelSpace
     * @param  string  $input
     * @param  bool  $ucfirst
     * @return string
     */
    public static function snakeToCamelCase(string $input, bool $ucfirst = false): string
    {
        $input = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($input))));

        return $ucfirst ? ucfirst($input) : lcfirst($input);
    }

    /**
     * Получить только буквы и цифры из строки
     * @param  string  $input
     * @return string
     */
    public static function getOnlyAlphaNumeric(string $input): string
    {
        return preg_replace('/[^A-Z0-9]+/i', '', $input);
    }

    /**
     * Получить буквы из строки
     * @param  string  $input
     * @return string
     */
    public static function getOnlyLetters(string $input): string
    {
        return preg_replace('/[^A-Z]+/i', '', $input);
    }

    /**
     * Получить цифры из строки
     * @param  string  $input
     * @return string
     */
    public static function getOnlyNumeric(string $input): string
    {
        return preg_replace('/[^0-9]+/i', '', $input);
    }

    /**
     * Подключение основных модулей
     * @return void
     */
    public static function includeBaseModules(): void
    {
        Loader::includeModule('highloadblock');
        Loader::includeModule('iblock');
        Loader::includeModule('pago.bitrixmodels');
    }
}
