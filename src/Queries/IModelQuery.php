<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\IModel;

/**
 * Запросы к инфоблокам
 */
final class IModelQuery
{
    /**
     * Класс модели
     * @var string
     */
    private string $model;

    /**
     * Стандартный select
     * @var array|string[]
     */
    private array $defaultSelect = ['*'];

    /**
     * @param string $model Класс модели
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * Получение элементов запроса
     * @param array $filter
     * @param array $select
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @param bool $includeProperties
     * @param int $cacheTtl
     * @return array
     */
    public function fetch(
        array $filter = [],
        array $select = ['*'],
        array $order = [],
        int $limit = 999_999_999_999,
        int $offset = 0,
        bool $includeProperties = false,
        int $cacheTtl = 0
    ): array {
        /**
         * @var IModel $model
         */
        $model = new $this->model();
        $data = [];
        $iblockId = $model::iblockId();
        /** @var string|null $iblockEntity */
        $entity = Iblock::wakeUp($iblockId)->getEntityDataClass();
        if (null === $entity) {
            throw new SystemException(
                sprintf(
                    'Ошибка инициализации инфоблока ID = %d. Заполните API_CODE инфоблока',
                    $iblockId
                )
            );
        }
        $cache = [];
        if ($cacheTtl) {
            $cache['cache'] = [
                'ttl' => $cacheTtl
            ];
        }
        $query = (new $entity())->getList(
            array_merge(
                [
                    'filter' => $filter,
                    'select' => $this->select(
                        $model,
                        $select,
                        $includeProperties
                    ),
                    'order'  => $order,
                    'limit'  => $limit,
                    'offset' => $offset
                ],
                $cache
            )
        );
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var ElementV2|ElementV1 $element
             * @var IModel $model
             */
            $model = clone $model;
            $data[] = $model->setElement($element);
        }

        return $data;
    }

    /**
     * Построитель select
     * @param IModel $model
     * @param array $select
     * @param bool $includeProperties
     * @return array
     */
    private function select(IModel $model, array $select, bool $includeProperties): array
    {
        if (! $select) {
            $select = $this->defaultSelect;
        }
        if ($includeProperties) {
            $select = array_merge(
                $this->defaultSelect,
                IModelHelper::getIblockPropertyCodes($model::iblockId())
            );
        }

        return array_unique($select);
    }
}
