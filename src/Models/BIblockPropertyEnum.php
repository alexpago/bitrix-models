<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Models;

use Pago\Bitrix\Models\TableModel;

/**
 * Таблица b_iblock_property_enum
 * @property int ID // ID
 * @property int PROPERTY_ID // PROPERTY_ID
 * @property string VALUE // VALUE
 * @property string DEF // DEF
 * @property int SORT // SORT
 * @property string XML_ID // XML_ID
 * @property string TMP_ID // TMP_ID
 * @method $this whereId(mixed $data, string $operator = '') // ID
 * @method $this wherePropertyId(mixed $data, string $operator = '') // PROPERTY_ID
 * @method $this whereValue(mixed $data, string $operator = '') // VALUE
 * @method $this whereXmlId(mixed $data, string $operator = '') // XML_ID
 */
final class BIblockPropertyEnum extends TableModel
{

}
