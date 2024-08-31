<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Traits;

/**
 * Вспомогательные методы построителя фильтрации
 */
trait ModelWhereTrait
{
    /**
     * Фильтр запроса
     * @var array
     */
    protected array $queryFilter = [];

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
        $this->queryFilter[$operator . $property] = $data;

        return $this;
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
        if (! $this->queryFilter) {
            return $this->where($property, $operator, $data);
        }
        $lastFilterKey = array_key_last($this->queryFilter);
        $lastFilterValue = end($this->queryFilter);
        if (!$lastFilterValue) {
            $this->queryFilter[$operator . $property] = $data;

            return $this;
        }
        // Filter or уже существует
        if (
            is_array($lastFilterValue)
            && array_key_exists('LOGIC', $lastFilterValue)
            && $lastFilterValue['LOGIC'] === 'OR'
        ) {
            // Дополним существующий or фильтр новым значением
            $this->queryFilter[$lastFilterKey][] = [
                $operator . $property => $data
            ];

            return $this;
        }
        // Filter or отсутствует
        unset($this->queryFilter[$lastFilterKey]);
        $this->queryFilter[] = [
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
        $this->queryFilter['!' . $property] = null;

        return $this;
    }

    /**
     * Фильтрация свойства по null
     * @param string $property
     * @return $this
     */
    public function whereNull(string $property): static
    {
        $this->queryFilter['=' . $property] = null;

        return $this;
    }
}
