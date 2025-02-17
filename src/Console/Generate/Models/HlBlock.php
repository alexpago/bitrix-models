<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate\Models;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Console\Generate\GenerateResult;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\HlModelHelper;

/**
 * Генерация моделей для highload блоков
 */
class HlBlock extends Base
{
    /**
     * @param int $id ID справочника
     * @param string|null $path Директория создания модели
     * @param string|null $namespace Namespace модели
     */
    public function __construct(
        int     $id,
        ?string $path = null,
        ?string $namespace = null
    )
    {
        $this->id = $id;
        $this->pathModels = $this->getModelPath($path, 'highload');
        $this->namespace = $namespace ?: $this->defaultModeNamespace . '\\Highload';
        $this->model = file_get_contents(__DIR__ . '/../Layouts/hlblock');
    }

    /**
     * Генерация модели
     * @return GenerateResult
     * @throws SystemException
     */
    public function generateModel(): GenerateResult
    {
        $data = $this->getModelData();
        $model = $this->model;
        $model = str_replace('#NAMESPACE#', $data->namespace, $model);
        $model = str_replace('#NAME#', $data->name, $model);

        // Генерация документации
        $properties = ''; $methods = '';
        foreach (HlModelHelper::getProperties($this->id) as $property) {
            if ('' !== $properties) {
                $properties .= PHP_EOL;
                $methods .= PHP_EOL;
            }
            $properties .= ' * ' . sprintf(
                    $this->layoutProperty,
                    $this->getPropertyReturnType($property['USER_TYPE_ID'], $property['MULTIPLE'] === 'Y'),
                    $property['CODE'],
                    ucfirst($property['NAME'])
                );
            $methods .= ' * ' . sprintf(
                    $this->layoutMethod,
                    '$this',
                    Helper::snakeToCamelCase($property['CODE'], true),
                    ucfirst($property['NAME'])
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
     * @throws SystemException
     */
    private function getModelData(): GenerateResult
    {
        $code = $this->getHlBlock()['NAME'];
        $filename = sprintf(
            '%s.php',
            strtolower(Helper::getOnlyAlphaNumeric($code))
        );

        return new GenerateResult(
            path: $this->pathModels . '/' . $filename,
            filename: $this->pathModels . '/' . $filename,
            namespace: $this->namespace,
            name: $code
        );
    }

    /**
     * @return array
     * @throws SystemException
     */
    private function getHlBlock(): array
    {
        return HighloadBlockTable::query()
            ->setSelect([
                'ID',
                'NAME',
                'TABLE_NAME'
            ])
            ->setFilter([
                '=ID' => $this->id
            ])
            ->fetch();
    }
}
