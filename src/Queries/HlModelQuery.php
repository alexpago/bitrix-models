<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\HlModel;

/**
 * Запросы к highload блокам
 */
final class HlModelQuery
{
    /**
     * Класс модели
     * @var string
     */
    private string $model;

    /**
     * @var array|string[]
     */
    private array $defaultSelect = ['*'];

    /**
     * @param string $model Класс модели
     */
    public function __construct(string $model)
    {
        $this->model = $model;
        Helper::includeBaseModules();
    }

    /**
     * @param  array  $filter
     * @param  array  $select
     * @param  array  $order
     * @param  int  $limit
     * @param  int  $offset
     * @return array
     */
    public function fetch(
        array $filter = [],
        array $select = ['*'],
        array $order = [],
        int $limit = 999_999_999_999,
        int $offset = 0
    ): array {
        /**
         * @var HlModel $model
         */
        $model = new $this->model();
        $data = [];
        $entity = $model::getEntityClass();
        if (null === $entity) {
            throw new SystemException(
                sprintf(
                    'Ошибка инициализации highload ID = %d',
                    $model::hlId()
                )
            );
        }
        /**
         * @var DataManager $entity
         */
        $entity = new $entity();
        $query = $entity->getList([
            'filter' => $filter,
            'select' => $select,
            'order'  => $order,
            'limit'  => $limit,
            'offset' => $offset
        ]);
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             * @var HlModel $model
             */
            $model = clone $model;
            $data[] = $model->setElement($element);
        }

        return $data;
    }
}
