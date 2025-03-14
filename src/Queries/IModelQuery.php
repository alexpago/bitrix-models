<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Main\SystemException;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\IModel;
use Pago\Bitrix\Models\Interfaces\QueryableInterface;

/**
 * Запросы к инфоблокам
 */
final class IModelQuery extends BaseQuery implements QueryableInterface
{
    /**
     * @var CommonElementTable
     */
    protected CommonElementTable $modelEntity;

    /**
     * @var int
     */
    protected int $iblockId;

    /**
     * @param string $modelClass
     * @param Builder $queryBuilder
     * @throws SystemException
     */
    public function __construct(string $modelClass, Builder $queryBuilder)
    {
        $entity = new $modelClass();
        if (! $entity instanceof IModel) {
            throw new SystemException('IModelQuery must be instance of IModel');
        }
        $this->modelEntity = $entity::getEntity();
        $this->iblockId = $entity->getIblockId();
        parent::__construct($modelClass, $queryBuilder);
    }

    /**
     * @return CommonElementTable
     */
    public function getEntity()
    {
        return $this->modelEntity;
    }

    /**
     * Получение элементов запроса
     * @return array
     */
    public function fetch(): array
    {
        /**
         * @var array<EntityObject> $elements
         * @var array<int> $elementIds
         */
        $elements = [];
        $elementIds = [];
        // Шаг 1: Первый запрос на получение системных полей
        $query = $this->getEntity()::getList($this->collectFetchFilter());
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             */
            $elements[] = $element;
            $elementIds[] = $element->getId();
        }

        // Шаг 2: Загрузка свойств инфоблока при указании withProperties или наличию свойств в select
        $properties = [];
        if ($this->queryBuilder->getWithProperties() || $this->hasPropertyFields()) {
            $properties = IModelHelper::getProperties(
                elements: $elementIds,
                iblockId: $this->iblockId,
                codes: $this->collectPropertyFields(),
                cacheTtl: $this->queryBuilder->getCacheTtl()
            );
        }

        // Шаг 3: Загрузка детальных ссылок элементов
        $detailPageUrls = [];
        if ($this->queryBuilder->getWithDetailPageUrl()) {
            $detailPageUrls = IModelHelper::getDetailPageUrl(
                elements: $elementIds,
                iblockId: $this->iblockId,
                cacheTtl: $this->queryBuilder->getCacheTtl()
            );
        }

        // Шаг 4: Создадим модель для каждого элемента объединив все данные
        $data = [];
        foreach ($elements as $element) {
            /**
             * @var ElementV1|ElementV2 $element
             * @var IModel $model
             */
            $model = new $this->model();
            $model = $model::setElement($model, $element, $this->queryBuilder);
            // Детальная страница элемента
            if (!empty($detailPageUrls[$model->ID])) {
                $model->detailPageUrl = $detailPageUrls[$model->ID];
                $model->fill([
                    'DETAIL_PAGE_URL' => $detailPageUrls[$model->ID]
                ]);
            }
            // Свойства
            if (is_array($properties[$model->ID] ?? null)) {
                $model->fill($properties[$model->ID]);
            }
            $data[] = $model;
        }
        return $data;
    }

    /**
     * Выбрать только системные свойства
     * @param array $select
     * @return array
     */
    private function collectBaseFields(array $select): array
    {
        if (in_array('*', $select)) {
            return ['*'];
        }
        return array_intersect($select, IModel::getBaseFields());
    }

    /**
     * Получить только свойства инфоблока
     * @return array
     */
    private function collectPropertyFields(): array
    {
        $iblockProperties = IModelHelper::getIblockPropertyCodes($this->queryBuilder->getModel()::iblockId());
        return array_intersect($this->queryBuilder->getSelect(), $iblockProperties) ?: $iblockProperties;
    }

    /**
     * Фильтр для Fetch запросов
     * @return array
     */
    private function collectFetchFilter(): array
    {
        $cache = [];
        if ($this->queryBuilder->getCacheTtl() > 0) {
            $cache = [
                'cache' => [
                    'ttl' => $this->queryBuilder->getCacheTtl(),
                    'cache_joins' => $this->queryBuilder->getCacheJoin()
                ]
            ];
        }
        return array_merge(
            [
                'filter' => $this->queryBuilder->getFilter(),
                'select' => $this->collectBaseFields($this->queryBuilder->getSelect()),
                'order' => $this->queryBuilder->getOrder(),
                'limit' => $this->queryBuilder->getLimit(),
                'offset' => $this->queryBuilder->getOffset(),
            ],
            $cache
        );
    }

    /**
     * В выборке участвуют поля свойств
     * @return bool
     */
    private function hasPropertyFields(): bool
    {
        return (bool)array_intersect(
            IModelHelper::getIblockPropertyCodes($this->queryBuilder->getModel()::iblockId()),
            $this->queryBuilder->getSelect()
        );
    }
}
