<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Iblock\ORM\ElementV1;
use Bitrix\Iblock\ORM\ElementV2;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Pago\Bitrix\Models\Data\ElementResult;
use Pago\Bitrix\Models\Helpers\Helper;

/**
 * Базовый класс моделей
 */
abstract class BaseModel
{
    /**
     * @var array
     */
    protected array $queryFilter = [];

    /**
     * @var array
     */
    protected array $querySelect = ['*'];

    /**
     * @var array|string[]
     */
    protected array $queryOrder = ['ID' => 'DESC'];

    /**
     * @var int
     */
    protected int $queryLimit = 999_999_999_999;

    /**
     * @var int
     */
    protected int $queryOffset = 0;

    /**
     * @var bool
     */
    protected bool $queryIsInit = false;

    /**
     * Кэш в секундах
     * @var int
     */
    public int $cacheTtl = 0;

    /**
     * Инициализация запроса
     * @return static
     */
    public static function query(): static
    {
        $static = new static();
        $static->queryIsInit = true;

        return $static;
    }

    /**
     * @param string $where
     * @param $operator
     * @param $data
     * @return $this
     */
    final public function where(string $where, $operator, $data = null): static
    {
        if (null === $data) {
            $data = $operator;
            $operator = '=';
        }
        $this->queryFilter[$operator . $where] = $data;

        return $this;
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
     * @param string $column
     * @param string $order
     * @return $this
     */
    final public function order(string $column, string $order = 'asc'): static
    {
        $this->queryOrder[$column] = $order;

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    final public function orderDesc(string $column): static
    {
        return $this->order($column, 'desc');
    }

    /**
     * Добавить кэширование запроса
     * @param int $ttl Время жизни кэша в секундах
     * @return $this
     */
    final public function withCache(int $ttl): static
    {
        $this->cacheTtl = $ttl;

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
     * Фильтрация свойства по not null
     * @param string $where
     * @return $this
     */
    public function whereNotNull(string $where): static
    {
        $this->queryFilter['!' . $where] = null;

        return $this;
    }

    /**
     * Фильтрация свойства по null
     * @param string $where
     * @return $this
     */
    public function whereNull(string $where): static
    {
        $this->queryFilter['=' . $where] = null;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function fill(array $data): static
    {
        foreach ($data as $parameter => $value) {
            $this->{$parameter} = $value;
        }

        return $this;
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
     * Построитель поиска
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

    /**
     * @param string $parameter
     * @param $value
     * @return void
     */
    public function __set(string $parameter, $value): void
    {
        $this->{$parameter} = $value;
    }

    /**
     * @param string $property
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        if (! property_exists($this, $property)) {
            return null;
        }

        return $this->{$property};
    }


    /**
     * Удаление текущего элемента или элементов запроса
     * @return array<ElementResult>
     */
    public function delete(): array
    {
        $result = [];
        $elements = [];
        if (null !== $this->element()) {
            $elements[] = $this->element();
        }
        if (! $elements && $this->queryIsInit) {
            if (! $this->queryFilter) {
                $result[] = new ElementResult(
                    elementId: 0,
                    success: false,
                    error: 'Нельзя удалить объекты без фильтра. Установить фильтр'
                );

                return $result;
            }
            $elements = $this->get();
        }
        if (! $elements) {
            return $result;
        }

        foreach ($elements as $element) {
            /**
             * @var ElementV1|ElementV2|EntityObject $element
             * @var Result $delete
             */
            if (method_exists($element, 'delete')) {
                $delete = $element->delete();
                $result[] = new ElementResult(
                    elementId: (int)$element->getId(),
                    success: $delete->isSuccess(),
                    error: $delete->getErrorMessages()
                );
            }
        }

        return $result;
    }

    /**
     * Удаление текущего элемента
     * @return bool
     */
    public function elementDelete(): bool
    {
        if (! $this->element()) {
            return false;
        }
        /**
         * @var ElementResult $delete
         */
        $delete = current($this->delete());

        return $delete->success;
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
}
