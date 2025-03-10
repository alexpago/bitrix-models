<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate\Models;

use Bitrix\Main\SystemException;

/**
 * Базовый класс генерации моделей
 */
abstract class Base
{
    // Стандартный путь моделей
    public const DEFAULT_MODEL_PATH = '/local/lib/models';

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
    protected string $layoutMethod = '@method Builder|%s where%s(mixed $data, string $operator = \'\') // %s';

    /**
     * Стандартный namespace
     * @var string
     */
    protected string $defaultModeNamespace = 'Local\\Models';

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

    /**
     * Получить путь генерации модели
     * @param string|null $path
     * @param string|null $pathPostfix
     * @return string
     */
    protected function getModelPath(?string $path, ?string $pathPostfix = null): string
    {
        // Путь не указан, зададим стандартный
        if (! is_string($path) || ! $path) {
            $path = $_SERVER['DOCUMENT_ROOT'] . $this::DEFAULT_MODEL_PATH;
            if ($pathPostfix) {
                $path .= '/' . $pathPostfix;
            }

            return $path;
        }

        if (str_contains($path, $_SERVER['DOCUMENT_ROOT'])) {
            return preg_replace('/\/+/', '/', $path);
        }

        return preg_replace('/\/+/', '/', $_SERVER['DOCUMENT_ROOT'] . '/' . $path);
    }
}
