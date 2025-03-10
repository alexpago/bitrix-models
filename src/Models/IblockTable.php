<?php

namespace Pago\Bitrix\Models\Models;

use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\TableModel;

/**
 * Таблица - b_iblock
 * @property int ID // ID
 * @property DateTime TIMESTAMP_X // TIMESTAMP_X
 * @property string IBLOCK_TYPE_ID // IBLOCK_TYPE_ID
 * @property string LID // LID
 * @property string CODE // CODE
 * @property string API_CODE // API_CODE
 * @property string NAME // NAME
 * @property string ACTIVE // ACTIVE
 * @property int SORT // SORT
 * @property string LIST_PAGE_URL // LIST_PAGE_URL
 * @property string DETAIL_PAGE_URL // DETAIL_PAGE_URL
 * @property string SECTION_PAGE_URL // SECTION_PAGE_URL
 * @property string CANONICAL_PAGE_URL // CANONICAL_PAGE_URL
 * @property int PICTURE // PICTURE
 * @property mixed DESCRIPTION // DESCRIPTION
 * @property string DESCRIPTION_TYPE // DESCRIPTION_TYPE
 * @property int RSS_TTL // RSS_TTL
 * @property string RSS_ACTIVE // RSS_ACTIVE
 * @property string RSS_FILE_ACTIVE // RSS_FILE_ACTIVE
 * @property int RSS_FILE_LIMIT // RSS_FILE_LIMIT
 * @property int RSS_FILE_DAYS // RSS_FILE_DAYS
 * @property string RSS_YANDEX_ACTIVE // RSS_YANDEX_ACTIVE
 * @property string XML_ID // XML_ID
 * @property string TMP_ID // TMP_ID
 * @property string INDEX_ELEMENT // INDEX_ELEMENT
 * @property string INDEX_SECTION // INDEX_SECTION
 * @property string WORKFLOW // WORKFLOW
 * @property string BIZPROC // BIZPROC
 * @property string SECTION_CHOOSER // SECTION_CHOOSER
 * @property string LIST_MODE // LIST_MODE
 * @property string RIGHTS_MODE // RIGHTS_MODE
 * @property string SECTION_PROPERTY // SECTION_PROPERTY
 * @property string PROPERTY_INDEX // PROPERTY_INDEX
 * @property int VERSION // VERSION
 * @property int LAST_CONV_ELEMENT // LAST_CONV_ELEMENT
 * @property int SOCNET_GROUP_ID // SOCNET_GROUP_ID
 * @property string EDIT_FILE_BEFORE // EDIT_FILE_BEFORE
 * @property string EDIT_FILE_AFTER // EDIT_FILE_AFTER
 * @property string SECTIONS_NAME // SECTIONS_NAME
 * @property string SECTION_NAME // SECTION_NAME
 * @property string ELEMENTS_NAME // ELEMENTS_NAME
 * @property string ELEMENT_NAME // ELEMENT_NAME
 * @property string REST_ON // REST_ON
 * @method $this whereId(mixed $data, string $operator = '') // ID
 * @method $this whereTimestampX(mixed $data, string $operator = '') // TIMESTAMP_X
 * @method $this whereIblockTypeId(mixed $data, string $operator = '') // IBLOCK_TYPE_ID
 * @method $this whereLid(mixed $data, string $operator = '') // LID
 * @method $this whereCode(mixed $data, string $operator = '') // CODE
 * @method $this whereApiCode(mixed $data, string $operator = '') // API_CODE
 * @method $this whereName(mixed $data, string $operator = '') // NAME
 * @method $this whereActive(mixed $data, string $operator = '') // ACTIVE
 * @method $this whereSort(mixed $data, string $operator = '') // SORT
 * @method $this whereListPageUrl(mixed $data, string $operator = '') // LIST_PAGE_URL
 * @method $this whereDetailPageUrl(mixed $data, string $operator = '') // DETAIL_PAGE_URL
 * @method $this whereSectionPageUrl(mixed $data, string $operator = '') // SECTION_PAGE_URL
 * @method $this whereCanonicalPageUrl(mixed $data, string $operator = '') // CANONICAL_PAGE_URL
 * @method $this wherePicture(mixed $data, string $operator = '') // PICTURE
 * @method $this whereDescription(mixed $data, string $operator = '') // DESCRIPTION
 * @method $this whereDescriptionType(mixed $data, string $operator = '') // DESCRIPTION_TYPE
 * @method $this whereRssTtl(mixed $data, string $operator = '') // RSS_TTL
 * @method $this whereRssActive(mixed $data, string $operator = '') // RSS_ACTIVE
 * @method $this whereRssFileActive(mixed $data, string $operator = '') // RSS_FILE_ACTIVE
 * @method $this whereRssFileLimit(mixed $data, string $operator = '') // RSS_FILE_LIMIT
 * @method $this whereRssFileDays(mixed $data, string $operator = '') // RSS_FILE_DAYS
 * @method $this whereRssYandexActive(mixed $data, string $operator = '') // RSS_YANDEX_ACTIVE
 * @method $this whereXmlId(mixed $data, string $operator = '') // XML_ID
 * @method $this whereTmpId(mixed $data, string $operator = '') // TMP_ID
 * @method $this whereIndexElement(mixed $data, string $operator = '') // INDEX_ELEMENT
 * @method $this whereIndexSection(mixed $data, string $operator = '') // INDEX_SECTION
 * @method $this whereWorkflow(mixed $data, string $operator = '') // WORKFLOW
 * @method $this whereBizproc(mixed $data, string $operator = '') // BIZPROC
 * @method $this whereSectionChooser(mixed $data, string $operator = '') // SECTION_CHOOSER
 * @method $this whereListMode(mixed $data, string $operator = '') // LIST_MODE
 * @method $this whereRightsMode(mixed $data, string $operator = '') // RIGHTS_MODE
 * @method $this whereSectionProperty(mixed $data, string $operator = '') // SECTION_PROPERTY
 * @method $this wherePropertyIndex(mixed $data, string $operator = '') // PROPERTY_INDEX
 * @method $this whereVersion(mixed $data, string $operator = '') // VERSION
 * @method $this whereLastConvElement(mixed $data, string $operator = '') // LAST_CONV_ELEMENT
 * @method $this whereSocnetGroupId(mixed $data, string $operator = '') // SOCNET_GROUP_ID
 * @method $this whereEditFileBefore(mixed $data, string $operator = '') // EDIT_FILE_BEFORE
 * @method $this whereEditFileAfter(mixed $data, string $operator = '') // EDIT_FILE_AFTER
 * @method $this whereSectionsName(mixed $data, string $operator = '') // SECTIONS_NAME
 * @method $this whereSectionName(mixed $data, string $operator = '') // SECTION_NAME
 * @method $this whereElementsName(mixed $data, string $operator = '') // ELEMENTS_NAME
 * @method $this whereElementName(mixed $data, string $operator = '') // ELEMENT_NAME
 * @method $this whereRestOn(mixed $data, string $operator = '') // REST_ON
 */
class IblockTable extends TableModel
{
    const TABLE_NAME = 'b_iblock';
}
