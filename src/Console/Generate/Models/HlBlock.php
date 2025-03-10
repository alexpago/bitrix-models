<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate\Models;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Console\Generate\GenerateResult;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\HlModelHelper;
use Pago\Bitrix\Models\Models\HlblockTable;

/**
 * Генерация моделей для highload блоков
 */
class HlBlock extends Base
{
    /**
     * @var HlblockTable
     */
    private HlblockTable $hlblock;

    /**
     * @param int $id ID справочника
     * @param string|null $path Директория создания модели
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
        $hlblock = HlblockTable::query()
            ->whereId($id)
            ->first();
        if (! $hlblock) {
            throw new SystemException('Ошибка. Не найден справочник ' . $id);
        }
        $this->hlblock = $hlblock;

        // Пути модели
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
     */
    private function getModelData(): GenerateResult
    {
        $code = $this->hlblock->NAME;
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
}
