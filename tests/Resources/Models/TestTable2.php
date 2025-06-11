<?php

namespace Pago\Bitrix\Tests\Resources\Models;

use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\Queries\Builder;
use Pago\Bitrix\Models\TableModel;

/**
 * Таблица - test_table2
 * @property int ID // ID
 * @property string FIELD // FIELD
 * @method static Builder|$this query()
 * @method Builder|$this get()
 * @method Builder|$this first()
 * @method Builder|$this whereId(mixed $data, string $operator = '') // ID
 * @method Builder|$this whereField(mixed $data, string $operator = '') // FIELD
 */
final class TestTable2 extends TableModel
{
    public const TABLE_NAME = 'test_table_2';
}
