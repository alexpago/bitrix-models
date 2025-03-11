<?php

namespace Pago\Bitrix\Tests\Resources\Models;

use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\HlModel;
use Pago\Bitrix\Models\Queries\Builder;

/**
 * HighloadBlock - TestHighloadBlock
 * @property int ID
 * @property array UF_LABELS
 * @property string UF_NAME
 * @property string UF_XML_ID
 * @property int UF_PRICE
 * @property DateTime|null UF_ACTIVE_FROM
 * @method static Builder|$this query()
 * @method Builder|$this get()
 * @method Builder|$this first()
 * @method Builder|$this whereId(mixed $data, string $operator = '') // ID
 */
final class TestHighloadBlock extends HlModel
{

}
