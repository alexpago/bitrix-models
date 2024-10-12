<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate\Models;

use Bitrix\Main\SystemException;

/**
 * Базовый класс генерации моделей
 */
abstract class Base
{
    /**
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * Стандартный namespace будущей модели
     * @var string
     */
    protected string $namespace;

    /**
     * Путь до расположения моделей
     * @var string
     */
    protected string $pathModels;

    /**
     * Шаблон модели
     * @var string
     */
    protected string $model;

    /**
     * Шаблон генерации свойств
     * @var string
     */
    protected string $layoutProperty = '@property %s %s // %s';

    /**
     * Шаблон генерации методов
     * @var string
     */
    protected string $layoutMethod = '@method %s where%s(mixed $data, string $operator = \'\') // %s';

    /**
     * Создание файла модели
     * @param  string  $path
     * @param  string  $content
     * @return bool
     * @throws SystemException
     */
    protected function createModelFile(string $path, string $content): bool
    {
        $parts = explode('/', $path);
        $file = array_pop($parts);
        $path = '';
        foreach ($parts as $part) {
            if (! is_dir($path .= '/' . $part)) {
                if (! mkdir($path)) {
                    throw new SystemException('Ошибка создания директории:' . $path);
                }
            }
        }

        return (bool)file_put_contents($path . '/' . $file, $content);
    }

    /**
     * Возвращаемый тип свойства полученным через свойство модели
     * @param  string  $type
     * @param  bool  $multiple
     * @return string
     */
    protected function getPropertyReturnType(string $type, bool $multiple): string
    {
        if ($multiple) {
            return 'array';
        }

        return match ($type) {
            'datetime' => 'DateTime',
            'date' => 'Date',
            'integer', 'int' => 'int',
            'boolean' => 'bool',
            'string', 'html', 'iblock_section', 'double', 'file', 'varchar', 'char', 'float', 'decimal' => 'string',
            default => 'mixed'
        };
    }
}
