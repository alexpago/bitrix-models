<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Main\SystemException;
use CIBlockElement;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Pago\Bitrix\Models\Cache\CacheService;
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
     * @param string $model Класс модели
     * @throws SystemException
     */
    public function __construct(string $model)
    {
        $entity = new $model();
        if (! $entity instanceof IModel) {
            throw new SystemException('IModelQuery must be instance of IModel');
        }
        $this->modelEntity = $entity::getEntity();
        parent::__construct($model);
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
     * @param Builder $builder
     * @return array
     */
    public function fetch(Builder $builder): array
    {
        /**
         * @var array<EntityObject> $elements
         * @var array<int> $elementIds
         */
        $elements = [];
        $elementIds = [];
        // Шаг 1: Первый запрос на получение системных полей
        $query = $this->getEntity()::getList($this->collectFetchFilter($builder));
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             */
            $elements[] = $element;
            $elementIds[] = $element->getId();
        }

        // Шаг 2: Загрузка свойств инфоблока при указании withProperties или наличию свойств в select
        $properties = [];
        if ($builder->getWithProperties() || $this->hasPropertyFields($builder)) {
            $properties = $this->getProperties(
                $builder,
                $elementIds
            );
        }

        // Шаг 3: Загрузка детальных ссылок элементов
        $detailPageUrls = [];
        if ($builder->getWithDetailPageUrl()) {
            $detailPageUrls = $this->getDetailPageUrl($builder, $elementIds);
        }

        // Шаг 4: Создадим модель для каждого элемента объединив все данные
        $data = [];
        foreach ($elements as $element) {
            /**
             * @var ElementV1|ElementV2 $element
             * @var IModel $model
             */
            $model = new $this->model();
            $model = $model::setElement($model, $element, $builder);
            // Детальная страница элемента
            if (! empty($detailPageUrls[$model->ID])) {
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
     * Получить свойства элемента через GetPropertyValuesArray
     * @param Builder $builder
     * @param IModel|int|array $elements
     * @return array
     */
    public function getProperties(
        Builder          $builder,
        IModel|int|array $elements,
    ): array
    {
        $iblockId = $builder->getModel()->getIblockId();
        // Соберем список идентификаторов элементов
        $elementIds = [];
        $elements = (array)$elements;
        if (array_is_list($elements)) {
            foreach ($elements as $element) {
                if (is_int($element)) {
                    $elementIds[] = $element;
                } elseif ($element instanceof IModel && $element->ID) {
                    $elementIds[] = $element->ID;
                }
            }
        }
        $elementIds = array_unique(array_filter($elementIds));
        if (! $elementIds) {
            return [];
        }
        // Проверка наличия кэша свойств
        $cacheKey = md5(serialize($elementIds)) . '-properties';
        $cache = CacheService::instance()->getIblockCache(
            iblockId: $iblockId,
            cacheKey: $cacheKey,
            ttl: $builder->getCacheTtl()
        );
        if (null !== $cache) {
            return $cache;
        }
        // Собираем массив свойств
        $data = [];
        CIBlockElement::GetPropertyValuesArray(
            result: $data,
            iblockID: $iblockId,
            filter: [
                'ID' => $elementIds
            ],
            propertyFilter: [
                'CODE' => $this->collectPropertyFields($builder)
            ]
        );
        // Запишем в кэш
        if ($builder->getWithProperties() && $data) {
            CacheService::instance()->setIblockCache(
                iblockId: $iblockId,
                cacheKey: $cacheKey,
                data: $data,
                ttl: $builder->getCacheTtl()
            );
        }

        return $data;
    }

    /**
     * Получение детальной страницы URL
     * @param Builder $builder
     * @param int|array $elementId
     * @return array<string>
     * @throws SystemException
     */
    public function getDetailPageUrl(Builder $builder, int|array $elementId): array
    {
        $iblockId = $builder->getModel()->getIblockId();
        $elementIds = (array)$elementId;
        // Проверка наличия кэша свойств
        $cacheKey = md5(serialize($elementIds)) . '-detail-page-url';
        $cache = CacheService::instance()->getIblockCache(
            iblockId: $iblockId,
            cacheKey: $cacheKey,
            ttl: $builder->getCacheTtl()
        );
        if (null !== $cache) {
            return $cache;
        }
        // Получение данных
        $data = [];
        $elements = CIBlockElement::getList(
            arFilter: [
                '=ID' => $elementIds,
            ],
            arSelectFields: [
                'ID',
                'DETAIL_PAGE_URL'
            ]
        );
        while ($element = $elements->GetNext()) {
            $data[(int)$element['ID']] = $element['DETAIL_PAGE_URL'];
        }
        // Запишем в кэш
        if ($builder->getWithProperties() && $data) {
            CacheService::instance()->setIblockCache(
                iblockId: $iblockId,
                cacheKey: $cacheKey,
                data: $data,
                ttl: $builder->getCacheTtl()
            );
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
     * @param Builder $builder
     * @return array
     */
    private function collectPropertyFields(Builder $builder): array
    {
        $iblockProperties = IModelHelper::getIblockPropertyCodes($builder->getModel()::iblockId());
        return array_intersect($builder->getSelect(), $iblockProperties) ?: $iblockProperties;
    }

    /**
     * Фильтр для Fetch запросов
     * @param Builder $builder
     * @return array
     */
    private function collectFetchFilter(Builder $builder): array
    {
        $cache = [];
        if ($builder->getCacheTtl() > 0) {
            $cache = [
                'cache' => [
                    'ttl' => $builder->getCacheTtl(),
                    'cache_joins' => $builder->getCacheJoin()
                ]
            ];
        }
        return array_merge(
            [
                'filter' => $builder->getFilter(),
                'select' => $this->collectBaseFields($builder->getSelect()),
                'order' => $builder->getOrder(),
                'limit' => $builder->getLimit(),
                'offset' => $builder->getOffset(),
            ],
            $cache
        );
    }

    /**
     * В выборке участвуют поля свойств
     * @param Builder $builder
     * @return bool
     */
    private function hasPropertyFields(Builder $builder): bool
    {
        return (bool)array_intersect(
            IModelHelper::getIblockPropertyCodes($builder->getModel()::iblockId()),
            $builder->getSelect()
        );
    }
}
