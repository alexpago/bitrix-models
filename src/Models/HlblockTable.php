<?php

namespace Pago\Bitrix\Models\Models;

use Pago\Bitrix\Models\TableModel;

/**
 * Таблица - b_hlblock_entity
 * @property int ID // ID
 * @property string NAME // NAME
 * @property string TABLE_NAME // TABLE_NAME
 * @method $this whereId(mixed $data, string $operator = '') // ID
 * @method $this whereName(mixed $data, string $operator = '') // NAME
 * @method $this whereTableName(mixed $data, string $operator = '') // TABLE_NAME
 */
class HlblockTable extends TableModel
{
    const TABLE_NAME = 'b_hlblock_entity';
}
