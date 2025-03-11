<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate\Models;

use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Console\Generate\GenerateResult;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\Models\IblockTable;

/**
 * Генерация моделей для инфоблоков
 */
class Iblock extends Base
{
    /**
     * @var IblockTable
     */
    private IblockTable $iblock;

    /**
     * @param int $id ID инфоблока
     * @param string|null $path Директория модели
     * @param string|null $namespace Namespace модели
     * @throws SystemException
     */
    public function __construct(
        int     $id,
        ?string $path = null,
        ?string $namespace = null
    )
    {
        $this->id = $id;
        $iblock = IblockTable::query()->whereId($id)->first();
        if (! $iblock) {
            throw new SystemException('Ошибка. Не найден инфоблок ' . $id);
        }
        $this->iblock = $iblock;

        // Пути модели
        $this->pathModels = $this->getModelPath($path, 'iblock');
        $this->namespace = $namespace ?: $this->defaultModeNamespace . '\\Iblock';
        $this->model = file_get_contents(__DIR__ . '/../Layouts/iblock');
    }

    /**
     * Генерация модели
     * @return GenerateResult
     * @throws SystemException
     */
    public function generateModel(): GenerateResult
    {
        $this->setApiCode();
        $data = $this->getModelData();
        $model = $this->model;
        $model = str_replace('#NAMESPACE#', $data->namespace, $model);
        $model = str_replace('#NAME#', $data->name, $model);

        // Генерация документации
        $properties =  $methods = '';
        foreach (IModelHelper::getIblockProperties($this->id) as $property) {
            $code = Helper::snakeToCamelCase($property['CODE'], true);
            if ('' !== $properties) {
                $properties .= PHP_EOL;
                $methods .= PHP_EOL;
            }
            $properties .= ' * ' . sprintf(
                    $this->layoutProperty,
                    $this->getPropertyReturnType($property['PROPERTY_TYPE'], $property['MULTIPLE'] === 'Y'),
                    $property['CODE'],
                    ucfirst($property['NAME'])
                );
            $methods .= ' * ' . sprintf(
                    $this->layoutMethod,
                    '$this',
                    $code,
                    ucfirst($property['NAME'])
                );
        }

        $model = str_replace('#PROPERTIES#', $properties, $model);
        $model = str_replace('#METHODS#', $methods, $model);
        $data->success = $this->createModelFile($data->path, $model);

        return $data;
    }

    /**
     * Установка API_CODE инфоблоку
     * @param string|null $apiCode
     * @return bool
     */
    public function setApiCode(?string $apiCode = null): bool
    {
        if ($this->iblock->API_CODE) {
            return true;
        }
        $code = $apiCode ?? $this->iblock->CODE;
        if (! $code) {
            $code = 'iblock' . $this->iblock->ID;
        }

        return $this->iblock->update([
            'API_CODE' => Helper::getOnlyAlphaNumeric(
                Helper::snakeToCamelCase($code, true)
            ),
        ])->isSuccess();
    }

    /**
     * Возвращаемый тип свойства полученным через свойство модели
     * @param  string  $type
     * @param  bool  $multiple
     * @return string
     */
    protected function getPropertyReturnType(string $type, bool $multiple): string
    {
        return $multiple ? 'array' : 'string';
    }

    /**
     * Сбор информации для генерации модели
     * @return GenerateResult
     */
    private function getModelData(): GenerateResult
    {
        $warnings = [];
        if (! $this->iblock->CODE) {
            $warnings[] = 'У инфоблока отсутствует символьный код. Рекомендуется установить его';
        }
        $name = $this->iblock->CODE;
        if ($name && ! Helper::isSnakeCaseString($name)) {
            $warnings[] = 'Инфоблок содержит код не в формате snake_case. Разрешенные символы a-z_';
        }
        if (! Helper::isSnakeCaseString($name)) {
            $name = 'iblock' . $this->iblock->ID;
        }
        $name = Helper::snakeToCamelCase($name, true);
        $filename = sprintf(
            '%s.php',
            strtolower(Helper::getOnlyAlphaNumeric($name))
        );
        $path = $this->pathModels . '/' . $filename;
        $namespace = $this->namespace;

        return new GenerateResult(
            path: $path,
            filename: $filename,
            namespace: $namespace,
            name: $name,
            warnings: $warnings
        );
    }
}
