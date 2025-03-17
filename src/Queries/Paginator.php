<?php

namespace Pago\Bitrix\Models\Queries;

use Bitrix\Main\Application;
use Bitrix\Main\Type\Contract\Arrayable;
use Pago\Bitrix\Models\BaseModel;

/**
 * Пагинация для запросов Builder
 * @template T of BaseModel
 */
final class Paginator implements Arrayable
{
    /**
     * Всего элементов
     * @var int
     */
    public int $total = 0;

    /**
     * Последняя страница
     * @var int
     */
    public int $lastPage = 1;

    /**
     * Элементы текущей страницы
     * @var BaseModel
     */
    public array $data = [];

    /**
     * Имеются ли еще страницы
     * @var bool
     */
    public bool $hasMorePages = false;

    /**
     * @param Builder $builder
     * @param int $perPage
     * @param string $pageName
     * @param int|null $currentPage
     */
    public function __construct(
        public Builder $builder,
        public int     $perPage,
        public string  $pageName = 'page',
        public ?int    $currentPage = null
    )
    {
        $this->perPage = $this->perPage ?: 1;
        // Определим текущую страницу
        if ($currentPage === null) {
            $request = Application::getInstance()->getContext()->getRequest();
            $this->currentPage = intval($request->getQuery('page'));
        }
        $this->currentPage = $this->currentPage ?: 1;
        // Определим количество элементов
        $this->total = $this->builder->count();
        // Последняя страница
        $this->lastPage = (int)ceil($this->total / $this->perPage);
        // Имеются ли еще страницы
        $this->hasMorePages = $this->currentPage < $this->lastPage;
        // Результат
        $this->data = $this->builder->get($this->perPage, ($this->currentPage - 1) * $this->perPage);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'perPage' => $this->perPage,
            'currentPage' => $this->currentPage,
            'lastPage' => $this->lastPage,
            'hasMorePages' => $this->hasMorePages,
            'data' => array_map(static fn(BaseModel $element) => $element->toArray(), $this->data),
        ];
    }
}