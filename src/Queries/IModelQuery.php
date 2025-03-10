<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use CIBlockElement;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
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
        Helper::includeBaseModules();
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
        /**
         * @var IModel $model
         */
        $model = new $this->model();
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
        $query = $this->getEntityClass($model)::getList(
            array_merge(
                [
                    'filter' => $builder->getFilter(),
                    'select' => $this->select(
                        $model,
                        $builder->getSelect(),
                        $builder->withProperties
                    ),
                    'order'  => $builder->getOrder(),
                    'limit'  => $builder->getLimit(),
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

        // Загрузка детальных ссылок элементов
        $detailPageUrls = [];
        if ($builder->withDetailPageUrl) {
            $detailPageUrls = $this->getDetailPageUrl($elementIds);
        }
        foreach ($elements as $element) {
            /**
             * @var ElementV1|ElementV2 $element
             * @var IModel $model
             */
            $model = clone $model;
            $model = $model::setElement($model, $element, $builder);
            if ($builder->withDetailPageUrl) {
                $model->detailPageUrl = $detailPageUrls[(int)$element->getId()];
                $model->fill([
                    'DETAIL_PAGE_URL' => $detailPageUrls[(int)$element->getId()]
                ]);
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
        $entity = $this->getEntityClass();

        return $entity::getList($parameters);
    }

    /**
     * Фасет GetCount
     * @param Builder $builder
     * @return int
     * @see CommonElementTable::getCount()
     */
    public function count(Builder $builder): int {
        return $this->getEntityClass()::getCount($builder->getFilter());
    }

    /**
     * Получение детальной страницы URL
     * @param int|array $elementId
     * @return array<string>
     */
    public function getDetailPageUrl(int|array $elementId): array
    {
        $data = [];
        /**
         * @var IModel $model
         */
        $model = new $this->model();
        $elements = CIBlockElement::getList(
            arFilter: [
                '=ID' => $elementId,
                '=IBLOCK_ID' => $model::iblockId()
            ],
            arSelectFields: [
                'ID',
                'DETAIL_PAGE_URL'
            ]
        );
        while ($element = $elements->GetNext()) {
            $data[(int)$element['ID']] = $element['DETAIL_PAGE_URL'];
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

    /**
     * Получение экземпляра класса
     * @param IModel|null $model
     * @return CommonElementTable
     */
    private function getEntityClass(?IModel $model = null): CommonElementTable
    {
        if (null === $model) {
            $model = new $this->model();
        }

        return $model::getEntity();
    }
}
