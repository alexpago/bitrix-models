<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Data;

/**
 * Результат выполнения запроса к элементу
 */
class ElementResult
{
    /**
     * Результат выполнения
     * @var bool
     */
    public bool $success;

    /**
     * Идентификатор элемента
     * @var int
     */
    public int $elementId;

    /**
     * Ошибка
     * @var array|null
     */
    public ?array $error;

    /**
     * @param int $elementId
     * @param bool $success
     * @param string|array|null $error
     */
    public function __construct(int $elementId, bool $success = true, string|array|null $error = null)
    {
        $this->elementId = $elementId;
        $this->success = $success;
        $this->error = null;
        if (null !== $error) {
            $this->error = is_array($error) ? $error : [$error];
        }
    }
}
