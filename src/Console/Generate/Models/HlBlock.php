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
     * @var string
     */
    private string $layoutProperty = '@property %s %s // %s';

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
        $this->pathModels = $_SERVER['DOCUMENT_ROOT'];
        $this->namespace = str_replace('Console\Generate\Models', 'Models', __NAMESPACE__);
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
        $properties = '';
        foreach (HlModelHelper::getProperties($this->id) as $property) {
            if ('' !== $properties) {
                $properties .= PHP_EOL;
            }
            $properties .= ' * ' . sprintf(
                    $this->layoutProperty,
                    $this->getPropertyReturnType($property['USER_TYPE_ID'], $property['MULTIPLE'] === 'Y'),
                    $property['CODE'],
                    ucfirst($property['NAME'])
                );
        }

        $model = str_replace('#PROPERTIES#', $properties, $model);
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

    /**
     * Возвращаемый тип свойства полученным через свойство модели
     * @param  string  $type
     * @param  bool  $multiple
     * @return string
     */
    private function getPropertyReturnType(string $type, bool $multiple): string
    {
        if ($multiple) {
            return 'array';
        }
        if ($type === 'datetime') {
            return 'DateTime';
        }
        if ($type === 'integer') {
            return 'int';
        }

        return 'string';
    }
}
