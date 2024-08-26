<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models;

use Bitrix\Main\Type\DateTime;

/**
 * Базовые свойства и методы модели инфоблока
 * @method int getId
 * @method string getDetailPageUrl
 * @method DateTime getTimestampX
 * @method int getModifiedBy
 * @method DateTime getDateCreate
 * @method int getCreatedBy
 * @method int getIblockSectionId
 * @method bool getActive
 * @method DateTime getActiveFrom
 * @method DateTime getActiveTo
 * @method int getSort
 * @method string getName
 * @method int getPreviewPicture
 * @method string getPreviewText
 * @method string getPreviewTextType
 * @method string getDetailPicture
 * @method string getDetailText
 * @method string getXmlId
 * @method string getCode
 * @method string getTags
 * @method $this whereId(int|array $id)
 * @method $this whereTimestampX(DateTime|array $date, string $operator = '')
 * @method $this whereModifiedBy(int|array $id)
 * @method $this whereDateCreate(DateTime|array $date, string $operator = '')
 * @method $this whereCreatedBy(int|array $id)
 * @method $this whereIblockSectionId(int|array $id)
 * @method $this whereActive(bool $active)
 * @method $this whereActiveFrom(DateTime $date, string $operator = '')
 * @method $this whereActiveTo(DateTime $date, string $operator = '')
 * @method $this whereSort(int|array $sort)
 * @method $this whereName(string|array $name)
 * @method $this wherePreviewPicture(int|array $picture)
 * @method $this wherePreviewText(string|array $input)
 * @method $this wherePreviewTextType(string|array $input)
 * @method $this whereDetailPicture(int|array $picture)
 * @method $this whereDetailText(string|array $input)
 * @method $this whereXmlId(string|array $input)
 * @method $this whereCode(string|array $code)
 * @method $this whereTags(string|array $input)
 */
class IModel extends BaseIModel
{

}
