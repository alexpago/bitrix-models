<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate\Models;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Console\Generate\GenerateResult;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\IModelHelper;

/**
 * Генерация моделей для инфоблоков
 */
class Iblock extends Base
{
    /**
     * @var string
     */
    private string $layoutMethodProperty = '@method %s get%s() // %s';

    /**
     * @param int $id ID инфоблока
     * @param string|null $path Директория модели
     * @param string|null $namespace Namespace модели
     */
    public function __construct(
        int     $id,
        ?string $path = null,
        ?string $namespace = null)
    {
        $this->id = $id;
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
        $properties = $propertyMethods = $methods = '';
        foreach (IModelHelper::getIblockProperties($this->id) as $property) {
            $code = Helper::snakeToCamelCase($property['CODE'], true);
            if ('' !== $properties) {
                $propertyMethods .= PHP_EOL;
                $properties .= PHP_EOL;
                $methods .= PHP_EOL;
            }
            $propertyMethods .= ' * ' . sprintf(
                    $this->layoutMethodProperty,
                    $this->getMethodPropertyReturnType($property['PROPERTY_TYPE'], $property['MULTIPLE'] === 'Y'),
                    $code,
                    ucfirst($property['NAME'])
                );
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

        $model = str_replace('#PROPERTIES#', $propertyMethods . $properties, $model);
        $model = str_replace('#METHODS#', $methods, $model);
        $data->success = $this->createModelFile($data->path, $model);

        return $data;
    }

    /**
     * Установка API_CODE инфоблоку
     * @param  string|null  $apiCode
     * @return bool
     */
    public function setApiCode(?string $apiCode = null): bool
    {
        $iblock = $this->getIblock();
        if (null !== $iblock['API_CODE']) {
            return true;
        }

        $code = $apiCode ?? $iblock['CODE'];
        if (! $code) {
            $code = 'iblock' . $iblock['ID'];
        }
        $update = IblockTable::update($iblock['ID'], [
            'API_CODE' => Helper::snakeToCamelCase($code, true)
        ]);

        return $update->isSuccess();
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

        return 'string';
    }

    /**
     * Сбор информации для генерации модели
     * @return GenerateResult
     * @throws SystemException
     */
    private function getModelData(): GenerateResult
    {
        $iblock = $this->getIblock();
        $warnings = [];
        if (empty($iblock['CODE'])) {
            $warnings[] = 'У инфоблока отсутствует символьный код. Рекомендуется установить его';
        }
        $code = $iblock['CODE'];
        if (! $code) {
            $code = 'iblock' . $iblock['ID'];
        }
        $code = Helper::snakeToCamelCase($code, true);
        $filename = sprintf(
            '%s.php',
            strtolower(Helper::getOnlyAlphaNumeric($code))
        );
        $path = $this->pathModels . '/' . $filename;
        $namespace = $this->namespace;

        /**
         * TODO: будет актуально при добавлении в модуль
         */
//        if (! empty($iblock['IBLOCK_TYPE_ID']) && ! is_numeric($iblock['IBLOCK_TYPE_ID'])) {
//            $catalog = strtolower(Helper::getOnlyAlphaNumeric($iblock['IBLOCK_TYPE_ID']));
//            $namespace .= sprintf('\\%s', Helper::snakeToCamelCase($iblock['IBLOCK_TYPE_ID'], true));
//            $path = $this->pathModels . '/' . $catalog . '/' . $filename;
//        }

        return new GenerateResult(
            path: $path,
            filename: $filename,
            namespace: $namespace,
            name: $code,
            warnings: $warnings
        );
    }

    /**
     * @return array
     * @throws SystemException
     */
    private function getIblock(): array
    {
        return IblockTable::query()
            ->setSelect([
                'ID',
                'IBLOCK_TYPE_ID',
                'NAME',
                'CODE',
                'API_CODE',
                'VERSION'
            ])
            ->setFilter([
                '=ID' => $this->id
            ])
            ->fetch();
    }

    /**
     * Возвращаемый тип свойства полученным через метод
     * @param  string  $type
     * @param  bool  $multiple
     * @return string
     */
    private function getMethodPropertyReturnType(string $type, bool $multiple): string
    {
        if ($multiple) {
            return 'Collection';
        }

        return 'ValueStorage';
    }
}
