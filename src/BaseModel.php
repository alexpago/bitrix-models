<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Traits\ModelDeleteTrait;
use Pago\Bitrix\Models\Traits\ModelUpdateTrait;
use Pago\Bitrix\Models\Traits\ModelWhereTrait;

/**
 * Базовый класс моделей
 */
#[\AllowDynamicProperties]
abstract class BaseModel
{
    use ModelWhereTrait;
    use ModelDeleteTrait;
    use ModelUpdateTrait;

    /**
     * @var array
     */
    protected array $queryFilter = [];

    /**
     * @var array
     */
    protected array $querySelect = [
        '*'
    ];

    /**
     * @var array|string[]
     */
    protected array $queryOrder = [
        'ID' => 'DESC'
    ];

    /**
     * @var int
     */
    protected int $queryLimit = 999_999_999_999;

    /**
     * @var int
     */
    protected int $queryOffset = 0;

    /**
     * Построитель запросов инициирован
     * @var bool
     */
    protected bool $queryIsInit = false;

    /**
     * Свойства модели
     * @var array
     */
    public array $properties = [];

    /**
     * Свойства при инициализации модели
     * @var array
     */
    public array $originalProperties = [];

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
     * Инициализация запроса
     * @return static
     */
    final public static function query(): static
    {
        $static = new static();
        $static->queryIsInit = true;

        return $static;
    }

    /**
     * @return $this
     */
    final public function setFilter(array $filter): static
    {
        $this->queryFilter = $filter;

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    final public function setLimit(int $limit): static
    {
        $this->queryLimit = $limit;

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    final public function limit(int $limit): static
    {
        return $this->setLimit($limit);
    }

    /**
     * @param int $offset
     * @return $this
     */
    final public function setOffset(int $offset): static
    {
        $this->queryOffset = $offset;

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    final public function offset(int $offset): static
    {
        return $this->setOffset($offset);
    }

    /**
     * @param array $select
     * @return $this
     */
    final public function setSelect(array $select): static
    {
        $this->querySelect = $select;

        return $this;
    }

    /**
     * @param mixed ...$arguments
     * @return $this
     */
    final public function select(...$arguments): static
    {
        foreach ($arguments as $select) {
            if (!is_array($select)) {
                $select = [$select];
            }
            foreach ($select as $item) {
                $this->querySelect[] = $item;
            }
        }

        return $this;
    }

    /**
     * @param array $order
     * @return $this
     */
    final public function setOrder(array $order): static
    {
        $this->queryOrder = $order;

        return $this;
    }

    /**
     * Добавить кэширование запроса
     * @param int $ttl Время жизни кэша в секундах
     * @param bool $withJoin Кэшировать JOIN
     * @return $this
     */
    final public function withCache(int $ttl = 3600, bool $withJoin = false): static
    {
        $this->cacheTtl = $ttl;
        $this->cacheJoin = $withJoin;

        return $this;
    }

    /**
     * Исключить кэширование запроса
     * @return $this
     */
    final public function withoutCache(): static
    {
        $this->cacheTtl = 0;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function fill(array $data): static
    {
        foreach ($data as $parameter => $value) {
            $this->properties[$parameter] = $value;
        }

        return $this;
    }

    /**
     * Сортировка
     * @param string $column
     * @param string $order
     * @return $this
     */
    public function order(string $column, string $order = 'asc'): static
    {
        $this->queryOrder[$column] = $order;

        return $this;
    }

    /**
     * Сортировка по убыванию
     * @param string $column
     * @return $this
     */
    public function orderDesc(string $column): static
    {
        return $this->order($column, 'desc');
    }

    /**
     * Первый элемент выборки
     * @return $this|null
     */
    public function first(): ?static
    {
        $elements = $this->get(1);

        return $elements ? $elements[0] : null;
    }

    /**
     * @return ElementV2|ElementV1|EntityObject|null
     */
    public function element(): ElementV2|ElementV1|EntityObject|null
    {
        return null;
    }

    /**
     * Результат запроса
     * @param int|null $limit
     * @param int|null $offset
     * @return array<static>
     */
    public function get(?int $limit = null, ?int $offset = null): array
    {
        return [];
    }

    /**
     * @param string $property
     * @param $value
     * @return void
     */
    public function __set(string $property, $value): void
    {
        $this->properties[$property] = $value;
    }

    /**
     * @param string $property
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        return $this->properties[$property] ?? null;
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

            return $this->where(
                $field,
                $operator,
                $arguments[0]
            );
        }

        return null;
    }
}
