<?php

namespace Pago\Bitrix\Tests\Resources\Models;

use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\Queries\Builder;
use Pago\Bitrix\Models\TableModel;

/**
 * Таблица - test_table
 * @property int ID // ID
 * @property string NAME // NAME
 * @property string XML_ID // XML_ID
 * @property string PRICE // PRICE
 * @property DateTime ACTIVE_FROM // ACTIVE_FROM
 * @method static Builder|$this query()
 * @method Builder|$this get()
 * @method Builder|$this first()
 * @method Builder|$this whereId(mixed $data, string $operator = '') // ID
 * @method Builder|$this whereName(mixed $data, string $operator = '') // NAME
 * @method Builder|$this whereXmlId(mixed $data, string $operator = '') // XML_ID
 * @method Builder|$this wherePrice(mixed $data, string $operator = '') // PRICE
 * @method Builder|$this whereActiveFrom(mixed $data, string $operator = '') // ACTIVE_FROM
 */
final class TestTable extends TableModel
{

}
