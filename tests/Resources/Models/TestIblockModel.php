<?php

namespace Pago\Bitrix\Tests\Resources\Models;

use Pago\Bitrix\Models\IModel;
use Pago\Bitrix\Models\Queries\Builder;

/**
 * Для вызова методов getProperty и получения значения используйте метод getValue()
 * @property int PRICE // Цена
 * @property array LABELS // Цена
 * @method static Builder|$this query()
 * @method Builder|$this get()
 * @method Builder|$this first()
 * @method Builder|$this whereLabels(mixed $data, string $operator = '') // Лейблы
 * @method Builder|$this wherePrice(mixed $data, string $operator = '') // Цена
 */
final class TestIblockModel extends IModel
{

}
