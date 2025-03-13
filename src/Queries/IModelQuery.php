<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Main\SystemException;
use CIBlockElement;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Pago\Bitrix\Models\Cache\CacheService;
use Pago\Bitrix\Models\Helpers\Helper;
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
     * @var CommonElementTable
     */
    private CommonElementTable $modelEntity;

    /**
     * @param string $model Класс модели
     */
    public function __construct(string $model)
    {
        $this->model = $model;
        Helper::includeBaseModules();
        $model = new $model();
        if (! $model instanceof IModel) {
            throw new SystemException('IModelQuery must be instance of IModel');
        }
        $this->modelEntity = $model::getEntity();
    }

    /**
     * @param string $model Класс модели
     * @return self
     */
    public static function instance(string $model): self
    {
        return new self($model);
    }

    /**
     * Получение элементов запроса
     * @param Builder $builder
     * @return array
     */
    public function fetch(Builder $builder): array
    {
        $data = [];
        $cache = [];
        if ($builder->cacheTtl > 0) {
            $cache = [
                'cache' => [
                    'ttl' => $builder->cacheTtl,
                    'cache_joins' => $builder->cacheJoin
                ]
            ];
        }
        /**
         * @var array<ElementV1|ElementV2> $elements
         * @var array<int> $elementIds
         */
        $elements = [];
        $elementIds = [];
        // В первом запросе мы забираем только системные поля, без свойств
        $query = $this->modelEntity::getList(
            array_merge(
                [
                    'filter' => $builder->getFilter(),
                    'select' => $this->collectBaseFields($builder->getSelect()),
                    'order' => $builder->getOrder(),
                    'limit' => $builder->getLimit(),
                    'offset' => $builder->getOffset(),
                ],
                $cache
            )
        );
        foreach ($query->fetchCollection() as $element) {
            /**
             * @var EntityObject $element
             */
            $elements[] = $element;
            $elementIds[] = $element->getId();
        }

        // Загрузка свойств при указании withProperties или наличию свойств в select
        $properties = [];
        if ($builder->withProperties || $this->hasPropertyFields($builder)) {
            $properties = $this->getProperties(
                $builder,
                $elementIds
            );
        }

        // Загрузка детальных ссылок элементов
        $detailPageUrls = [];
        if ($builder->withDetailPageUrl) {
            $detailPageUrls = $this->getDetailPageUrl($builder, $elementIds);
        }

        // Создадим модель для каждого элемента
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
     * Фасет GetList
     * @param array $parameters
     * @return QueryResult
     * @see CommonElementTable::getList()
     */
    public function getList(array $parameters = []): QueryResult
    {
        return $this->modelEntity::getList($parameters);
    }

    /**
     * Фасет GetCount
     * @param Builder $builder
     * @return int
     * @see CommonElementTable::getCount()
     */
    public function count(Builder $builder): int
    {
        return $this->modelEntity::getCount($builder->getFilter());
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
            ttl: $builder->cacheTtl
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
        if ($builder->withProperties && $data) {
            CacheService::instance()->setIblockCache(
                iblockId: $iblockId,
                cacheKey: $cacheKey,
                data: $data,
                ttl: $builder->cacheTtl
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
            ttl: $builder->cacheTtl
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
        if ($builder->withProperties && $data) {
            CacheService::instance()->setIblockCache(
                iblockId: $iblockId,
                cacheKey: $cacheKey,
                data: $data,
                ttl: $builder->cacheTtl
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
