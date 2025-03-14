<?php

namespace Pago\Bitrix\Models\Interfaces;

use Bitrix\Main\ORM\Data\Result;

/**
 * Интерфейс моделей
 */
interface ModelInterface
{
    /**
     * @param bool $callEvents
     * @return Result
     */
    public function save(bool $callEvents = true): Result;

    /**
     * @param array $data
     * @param bool $callEvents
     * @return Result
     */
    public function update(array $data, bool $callEvents = true): Result;

    /**
     * @param bool $callEvents
     * @return $this
     */
    public function put(bool $callEvents = true): static;

    /**
     * @param bool $callEvents
     * @return Result
     */
    public function delete(bool $callEvents = true): Result;

    /**
     * @return bool
     */
    public function exists(): bool;

    /**
     * @return $this
     */
    public function refresh(): static;
}
