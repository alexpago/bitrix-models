<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

use Bitrix\Main\DB\SqlExpression;

/**
 * Вспомогательные методы построителя фильтрации
 */
trait ModelWhereTrait
{
    /**
     * Фильтрация
     * @param string $property
     * @param $operator
     * @param $data
     * @return $this
     */
    public function where(string $property, $operator, $data = null): static
    {
        if (null === $data) {
            $data = $operator;
            $operator = '=';
        }
        $this->filter[$operator . $property] = $data;

        return $this;
    }

    /**
     * @param string $property
     * @param array $values
     * @return $this
     */
    public function whereIn(string $property, array $values): static
    {
        return $this->where($property, '=', $values);
    }

    /**
     * @param string $property
     * @param array $values
     * @return $this
     */
    public function orWhereIn(string $property, array $values): static
    {
        return $this->orWhere($property, $values);
    }

    /**
     * @param string $property
     * @param string $property2
     * @return $this
     */
    public function whereProperty(string $property, string $property2): static
    {
        return $this->where($property, new SqlExpression($property2));
    }

    /**
     * @param string $property
     * @param string $property2
     * @return $this
     */
    public function orWhereProperty(string $property, string  $property2): static
    {
        return $this->orWhere($property, new SqlExpression($property2));
    }

    /**
     * @param string $column
     * @param string $column2
     * @return $this
     */
    public function whereColumn(string $column, string  $column2): static
    {
        return $this->where($column, new SqlExpression($column2));
    }

    /**
     * @param string $column
     * @param string $column2
     * @return $this
     */
    public function orWhereColumn(string $column, string  $column2): static
    {
        return $this->orWhere($column, new SqlExpression($column2));
    }

    /**
     * @param string $property
     * @param array $values
     * @return $this
     */
    public function whereNotIn(string $property, array $values): static
    {
        return $this->where($property, '!', $values);
    }

    /**
     * Фильтрация OR
     * @param string $property
     * @param $operator
     * @param $data
     * @return $this
     */
    public function orWhere(string $property, $operator, $data = null): static
    {
        if (null === $data) {
            $data = $operator;
            $operator = '=';
        }

        // Фильтр пустой, заполнять нечем
        if (! $this->filter) {
            return $this->where($property, $operator, $data);
        }
        $lastFilterKey = array_key_last($this->filter);
        $lastFilterValue = end($this->filter);
        if (! $lastFilterValue) {
            $this->filter[$operator . $property] = $data;

            return $this;
        }
        // Filter or уже существует
        if (
            is_array($lastFilterValue)
            && array_key_exists('LOGIC', $lastFilterValue)
            && $lastFilterValue['LOGIC'] === 'OR'
        ) {
            // Дополним существующий or фильтр новым значением
            $this->filter[$lastFilterKey][] = [
                $operator . $property => $data
            ];

            return $this;
        }
        // Filter or отсутствует
        unset($this->filter[$lastFilterKey]);
        $this->filter[] = [
            'LOGIC' => 'OR',
            [
                $lastFilterKey => $lastFilterValue
            ],
            [
                $operator . $property => $data
            ]
        ];

        return $this;
    }

    /**
     * Фильтрация свойства по not null
     * @param string $property
     * @return $this
     */
    public function whereNotNull(string $property): static
    {
        $this->filter['!' . $property] = null;

        return $this;
    }

    /**
     * Фильтрация свойства по null
     * @param string $property
     * @return $this
     */
    public function whereNull(string $property): static
    {
        $this->filter['=' . $property] = null;

        return $this;
    }

    /**
     * @param string $property
     * @param $min
     * @param $max
     * @return $this
     */
    public function whereBetween(string $property, $min, $max): static
    {
        return $this->where($property, '><', [$min, $max]);
    }

    /**
     * @param string $property
     * @param $min
     * @param $max
     * @return $this
     */
    public function whereNotBetween(string $property, $min, $max): static
    {
        return $this->where($property, '!><', [$min, $max]);
    }
}
