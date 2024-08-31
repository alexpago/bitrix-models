<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use CIBlockElement;
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
     * @param string $model Класс модели
     * @return self
     */
    public static function instance(string $model): self
    {
        return new self($model);
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
        bool $withDetailPageUrl = false,
        int $cacheTtl = 0,
        bool $cacheJoin = false
    ): array {
        /**
         * @var IModel $model
         */
        $model = new $this->model();
        $data = [];
        $cache = [];
        if ($cacheTtl > 0) {
            $cache = [
                'cache' => [
                    'ttl' => $cacheTtl,
                    'cache_joins' => $cacheJoin
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
             * @var EntityObject $element
             */
            $elements[] = $element;
            $elementIds[] = $element->getId();
        }

        // Загрузка детальных ссылок элементов
        $detailPageUrls = [];
        if ($withDetailPageUrl) {
            $detailPageUrls = $this->getDetailPageUrl($elementIds);
        }

        foreach ($elements as $element) {
            /**
             * @var ElementV1|ElementV2 $element
             * @var IModel $model
             */
            $model = clone $model;
            if ($withDetailPageUrl) {
                $model->detailPageUrl = $detailPageUrls[(int)$element->getId()];
            }
            $data[] = $model->setElement($element);
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
     * @param array $filter
     * @return int
     * @see CommonElementTable::getCount()
     */
    public function count(array $filter = []): int {
        $entity = $this->getEntityClass();

        return $entity::getCount($filter);
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
