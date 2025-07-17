<?php

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Main\ORM\Data\Result;
use Pago\Bitrix\Models\BaseModel;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\IModel;
use Pago\Bitrix\Models\Traits\ModelActionTrait;
use Pago\Bitrix\Models\Traits\ModelBaseTrait;
use Pago\Bitrix\Models\Traits\ModelRelationTrait;
use Pago\Bitrix\Models\Traits\ModelWhereTrait;

/**
 * Генерация запросов к моделям
 *
 * @template T of BaseModel
 */
final class Builder
{
    use ModelBaseTrait;
    use ModelWhereTrait {
        orWhere as protected traitOrWhere;
    }
    use ModelActionTrait;
    use ModelRelationTrait;

    /**
     * @var array
     */
    protected array $filter = [];

    /**
     * @var array
     */
    protected array $select = [];

    /**
     * @var array|string[]
     */
    protected array $order = [];

    /**
     * @var int
     */
    protected int $limit = 999_999_999_999;

    /**
     * @var int
     */
    protected int $offset = 0;

    /**
     * Свойства модели
     * @var array
     */
    protected array $properties = [];

    /**
     * Кэш в секундах
     * @var int
     */
    public int $cacheTtl = 0;

    /**
     * Кэширование JOIN
     * @var bool
     */
    public bool $cacheJoin = false;

    /**
     * @param BaseModel $model
     */
    public function __construct(
        protected BaseModel $model
    )
    {
    }

    /**
     * Получить модель
     * @return BaseModel
     */
    public function getModel(): BaseModel
    {
        return $this->model;
    }

    /**
     * Добавление элементов
     * @param array $data
     * @return array
     */
    public function insert(array $data): array
    {
        return $this->model::insert($data);
    }

    /**
     * Добавление элемента
     * @param array $data
     * @return Result
     */
    public function add(array $data): Result
    {
        return $this->model::add($data);
    }

    /**
     * Пагинация запроса
     * @param int $perPage
     * @param string $pageName
     * @param int|null $currentPage
     * @return Paginator
     */
    public function getPaginate(
        int    $perPage,
        string $pageName = 'page',
        ?int   $currentPage = null
    ): Paginator
    {
        return new Paginator(
            builder: $this,
            perPage: $perPage,
            pageName: $pageName,
            currentPage: $currentPage
        );
    }

    /**
     * Получить список элементов
     * @param int|null $limit
     * @param int|null $offset
     * @return T[]
     */
    public function get(?int $limit = null, ?int $offset = null): array
    {
        if (null !== $limit) {
            $this->limit($limit);
        }
        if (null !== $offset) {
            $this->offset($offset);
        }

        return $this->model::get($this);
    }

    /**
     * Результат запроса в виде массива
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function getArray(?int $limit = null, ?int $offset = null): array
    {
        $result = [];
        foreach ($this->get($limit, $offset) as $element) {
            $result[] = $element->toArray();
        }

        return $result;
    }

    /**
     * Получить первый элемент
     * @return T
     */
    public function first(): ?BaseModel
    {
        return $this->get(1)[0] ?? null;
    }

    /**
     * Первый элемент запроса массивом
     * @param bool $relations Включить связи для инфоблока (IBLOCK_ELEMENT_ID)
     * @return array|null
     */
    public function firstArray(bool $relations = false): ?array
    {
        $element = $this->first();

        return $element?->toArray($relations);
    }

    /**
     * Получить количество элементов
     * @return int
     */
    public function count(): int
    {
        return $this->model::count($this);
    }

    /**
     * Проверка существования элемента
     * @return bool
     */
    public function exists(): bool
    {
        return $this->first() !== null;
    }

    /**
     * Магические методы.
     * whereColumn(value) - построитель фильтра
     * @param string $name
     * @param array $arguments
     * @return $this|null
     */
    public function __call(string $name, array $arguments)
    {
        // Построитель поиска
        if (preg_match('/where([a-z])/i', $name)) {
            $field = strtoupper(Helper::camelToSnakeCase(str_replace('where', '', $name)));
            $operator = $arguments[1] ?? '=';

            return $this->where($field, $operator, $arguments[0]);
        }

        return null;
    }

    /**
     * Установка пагинации
     * @param int $page
     * @param int $itemsPerPage
     * @return $this
     */
    public function paginate(int $page = 1, int $itemsPerPage = 20): self
    {
        $offset = 0;
        if ($page > 1) {
            $offset = ($page - 1) * $itemsPerPage;
        }

        return $this->setOffset($offset)->setLimit($itemsPerPage);
    }

    /**
     * Модель является инфоблоком
     * @return bool
     */
    protected function isIblockModel(): bool
    {
        return $this->model instanceof IModel;
    }
}
