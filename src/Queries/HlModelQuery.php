<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\HlModel;
use Pago\Bitrix\Models\Interfaces\QueryableInterface;

/**
 * Запросы к highload блокам
 */
final class HlModelQuery extends BaseQuery implements QueryableInterface
{
    /**
     * @var DataManager
     */
    private DataManager $modelEntity;

    /**
     * @param string $model Класс модели
     * @throws SystemException
     */
    public function __construct(string $model)
    {
        /**
         * @var HlModel $entity
         */
        $entity = new $model();
        if (! $entity instanceof HlModel) {
            throw new SystemException('Model query must be instance of HlModel');
        }
        $this->modelEntity = new ($entity::getEntityClass());
        parent::__construct($model);
    }

    /**
     * @return DataManager
     */
    public function getEntity()
    {
        return $this->modelEntity;
    }
}
