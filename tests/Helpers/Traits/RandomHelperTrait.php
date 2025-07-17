<?php

namespace Pago\Bitrix\Tests\Helpers\Traits;

use Bitrix\Main\Security\Random;

/**
 * Генерация случайных значений
 */
trait RandomHelperTrait
{
    /**
     * @param int $min
     * @param int $max
     * @return int
     */
    private function getRandomPrice(int $min = 0, int $max = 100_00_00): int
    {
        return rand($min, $max);
    }

    /**
     * @param int $count
     * @param int $length
     * @return array
     */
    private function getRandomLabels(int $count = 3, int $length = 10): array
    {
        $labels = [];
        for ($i = 0; $i < $count; $i++) {
            $labels[] = Random::getString($length);
        }
        return $labels;
    }
}