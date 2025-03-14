<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

/**
 * Вспомогательные статические методы
 */
final class Helper
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
        // Заменим нижний слэш и тире
        $input = str_replace(['_', '-'], ' ', strtolower($input));
        // Заменим пробелы на верхний символ
        $input = str_replace(' ', '', ucwords($input));

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
     * @return int
     */
    public static function getOnlyNumeric(string $input): int
    {
        return (int)preg_replace('/[^0-9]+/i', '', $input);
    }

    /**
     * Проверка является ли строка camelSpace
     * @param string|null $string $string
     * @return bool
     */
    public static function isCamelCaseString(string|null $string): bool
    {
        if (! $string) {
            return false;
        }
        preg_match('/([A-Za-z]+)/', $string, $matches);
        if (! $matches || empty($matches[1])) {
            return false;
        }
        return strlen($matches[1]) === strlen($string);
    }

    /**
     * Проверка является ли строка snake_case
     * @param string|null $string $string
     * @return bool
     */
    public static function isSnakeCaseString(string|null $string): bool
    {
        if (! $string) {
            return false;
        }
        preg_match('/([a-z_]+)/', $string, $matches);
        if (! $matches || empty($matches[1])) {
            return false;
        }
        return strlen($matches[1]) === strlen($string);
    }
}
