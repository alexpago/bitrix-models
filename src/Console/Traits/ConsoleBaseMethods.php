<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Traits;

/**
 * Базовые методи консоли
 */
trait ConsoleBaseMethods
{
    /**
     * Аргументы консоли
     * @param  array  $arguments
     * @return array
     */
    public function getArguments(array $arguments): array
    {
        $result = [];

        foreach ($arguments as $argument) {
            preg_match('/--([\w\-]+)=([\w\-\/\\\]+)/', $argument, $argumentNameAndData);
            if (! $argumentNameAndData) {
                $result[$argument] = '';
            }
            $name = $argumentNameAndData[1] ?? null;
            $value = $argumentNameAndData[2] ?? null;
            if (null === $name || null === $value) {
                continue;
            }
            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * @param  array  $arguments
     * @return string|null
     */
    public function getMethodFromInputArguments(array $arguments): ?string
    {
        return array_key_exists(0, $arguments) ? $arguments[0] : null;
    }
}
