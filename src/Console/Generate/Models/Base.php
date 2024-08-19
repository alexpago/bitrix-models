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
     * @var int
     */
    protected int $id;

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
}
