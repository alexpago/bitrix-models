<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate\Models;

use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Console\Generate\GenerateResult;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\TableModelHelper;

/**
 * Генерация моделей для таблиц
 */
class Table extends Base
{
    /**
     * @param string $tableName Название таблицы
     * @param ?string $path Директория модели
     * @param string|null $namespace Namespace модели
     */
    public function __construct(
        public string  $tableName,
        public ?string $path = null,
        ?string        $namespace = null
    )
    {
        $this->pathModels = $this->getModelPath($path);
        $this->namespace = $namespace ?: $this->defaultModeNamespace;
        $this->model = file_get_contents(__DIR__ . '/../Layouts/table');
    }

    /**
     * Генерация модели
     * @return GenerateResult
     * @throws SystemException
     */
    public function generateModel(): GenerateResult
    {
        $data = $this->getModelData();
        // Создание модели возможно только для MySQL
        if (! TableModelHelper::instance()->isMysqlConnect()) {
            $data->warnings[] = 'Автоматические создание моделей возможно только для MySQL';

            return $data;
        }
        $modelName = Helper::snakeToCamelCase($data->name, true);
        $model = $this->model;
        $model = str_replace('#NAMESPACE#', $data->namespace, $model);
        $model = str_replace('#TABLE_NAME#', $data->name, $model);
        $model = str_replace('#NAME#', $modelName, $model);

        // Генерация документации
        $properties = ''; $methods = '';
        foreach (TableModelHelper::instance()->getTableColumns($this->tableName) as $property) {
            if ('' !== $properties) {
                $properties .= PHP_EOL;
                $methods .= PHP_EOL;
            }
            $properties .= ' * ' . sprintf(
                    $this->layoutProperty,
                    $this->getPropertyReturnType($property['type'], false),
                    $property['name'],
                    ucfirst($property['name'])
                );
            $methods .= ' * ' . sprintf(
                    $this->layoutMethod,
                    '$this',
                    Helper::snakeToCamelCase($property['name'], true),
                    ucfirst($property['name'])
                );
        }

        $model = str_replace('#PROPERTIES#', $properties, $model);
        $model = str_replace('#METHODS#', $methods, $model);

        $data->success = $this->createModelFile($data->path, $model);

        return $data;
    }

    /**
     * Сбор информации для генерации модели
     * @return GenerateResult
     */
    private function getModelData(): GenerateResult
    {
        $filename = sprintf(
            '%s.php',
            strtolower(Helper::getOnlyAlphaNumeric($this->tableName))
        );

        return new GenerateResult(
            path: $this->pathModels . '/' . $filename,
            filename: $this->pathModels . '/' . $filename,
            namespace: $this->namespace,
            name: $this->tableName
        );
    }
}
