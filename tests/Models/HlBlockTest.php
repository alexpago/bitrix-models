<?php

namespace Pago\Bitrix\Tests\Models;

use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\Helpers\HlModelHelper;
use Pago\Bitrix\Tests\Helpers\HlBlockCreatorHelper;
use Pago\Bitrix\Tests\Helpers\HlBlockDeleteHelper;
use Pago\Bitrix\Tests\Resources\Models\TestHighloadBlock;
use PHPUnit\Framework\TestCase;
use CModule;

/**
 * Тестирование моделей справочника
 */
final class HlBlockTest extends TestCase
{
    // Название - код тестового справочника
    public const HLBLOCK_NAME = 'TestHighloadBlock';

    // Название таблицы тестового справочника
    public const HLBLOCK_TABLE_NAME = 'test_highload_block';

    /**
     * @var int|null
     */
    public static ?int $hlblockId = null;

    /**
     * @return void
     * @throws \Exception
     */
    public static function setUpBeforeClass(): void
    {
        CModule::IncludeModule('highloadblock');

        // Удалим старый справочник при наличии
        $oldHlblockId = HlModelHelper::getHlIdByCode(self::HLBLOCK_NAME);
        if ($oldHlblockId) {
            HlModelHelper::clearHighloadModelCache();
            HlBlockDeleteHelper::deleteHighloadBlock($oldHlblockId);
        }

        // Используем вспомогательный класс для создания инфоблока
        self::$hlblockId = HlBlockCreatorHelper::createHighload(
            name: self::HLBLOCK_NAME,
            tableName: self::HLBLOCK_TABLE_NAME
        ) ?: null;
        if (! is_int(self::$hlblockId)) {
            self::fail('Ошибка создания справочника');
        }

        // Добавление свойств
        $properties = [
            [
                'FIELD_NAME' => 'UF_NAME',
                'USER_TYPE_ID' => 'string', // Строка
            ],
            [
                'FIELD_NAME' => 'UF_XML_ID',
                'USER_TYPE_ID' => 'string', // Строка
            ],
            [
                'FIELD_NAME' => 'UF_PRICE',
                'USER_TYPE_ID' => 'double', // Число
            ],
            [
                'FIELD_NAME' => 'UF_ACTIVE_FROM',
                'MULTIPLE' => 'N',
                'USER_TYPE_ID' => 'datetime', // Дата и время
            ],
            [
                'MULTIPLE' => 'Y',
                'FIELD_NAME' => 'UF_LABELS',
                'USER_TYPE_ID' => 'string', // Строка
            ]
        ];

        HlBlockCreatorHelper::addFields(self::$hlblockId, $properties);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function tearDownAfterClass(): void
    {
        HlBlockDeleteHelper::deleteHighloadBlock(self::$hlblockId);
    }

    /**
     * Добавление элемента из запроса
     * @return void
     */
    public function testQueryAddElement()
    {
        $data = [
            'UF_NAME' => Random::getString(10),
            'UF_PRICE' => $this->getRandomPrice(),
            'UF_LABELS' => $this->getRandomLabels(),
        ];

        $add = TestHighloadBlock::query()->add($data);
        $this->assertTrue($add->isSuccess());
        $element = TestHighloadBlock::query()
            ->withProperties()
            ->whereId($add->getId())
            ->first();
        $this->assertNotEmpty($element);
        if ($element) {
            $this->assertEquals($element->ID, $add->getId());
            $this->assertEquals($data['NAME'], $element->NAME);
            $this->assertEquals($data['PRICE'], $element->PRICE);
            $this->assertEquals($data['LABELS'], $element->LABELS);
        }
    }

    /**
     * Добавление нескольких элементов из запроса
     * @return void
     */
    public function testQueryInsertElements()
    {
        // Генерируем элементы
        $insertElements = [];
        for ($i = 0; $i < Random::getInt(1, 10); $i++) {
            $insertElements[] = [
                'UF_NAME' => Random::getString(10),
                'UF_PRICE' => $this->getRandomPrice(),
                'UF_LABELS' => $this->getRandomLabels(),
                'UF_XML_ID' => Random::getString(10)
            ];
        }
        // Вставляем их через query
        $elementIds = [];
        foreach (TestHighloadBlock::query()->insert($insertElements) as $result) {
            $elementIds[] = $result->getId();
            $this->assertTrue($result->isSuccess());
        }
        // Проверяем созданные элементы
        $this->assertSameSize(
            $insertElements,
            TestHighloadBlock::query()->whereId($elementIds)->get()
        );
        // Проверяем созданные элементы по содержимому
        $this->assertSameSize(
            $insertElements,
            TestHighloadBlock::query()->whereUfXmlId(array_column($insertElements, 'UF_XML_ID'))->get()
        );
        $this->assertSameSize(
            $insertElements,
            TestHighloadBlock::query()
                ->whereId($elementIds)
                ->whereUfPrice(array_column($insertElements, 'UF_PRICE'))
                ->get()
        );
    }

    /**
     * Тестирование удаления элементов
     * @return void
     */
    public function testQueryDeleteElements()
    {
        // Создадим случайно несколько элементов
        $elements = $this->createRandomElements(10);
        // Количество элементов до удаления
        $countBefore = TestHighloadBlock::query()->count();
        // Удалим 3 элемента из ранее созданных
        TestHighloadBlock::query()
            ->whereId(array_slice($elements, 0, 3))
            ->delete();
        // Посчитаем количество элементов сейчас
        $this->assertEquals($countBefore - 3, TestHighloadBlock::query()->count());
        // Посчитаем количество по ранее созданным элементам
        $this->assertEquals(7, TestHighloadBlock::query()->whereId($elements)->count());
    }

    /**
     * Добавление элемента из модели
     * @return void
     */
    public function testAddElement()
    {
        $price = 1000;
        $labels = ['test label', 'test label 2', 'test label 3'];
        // Создадим случайный элемент
        $elementId = $this->createRandomElements(
            price: $price,
            labels: $labels,
        )[0];
        // Проверим существование элемента
        $element = TestHighloadBlock::query()
            ->withProperties()
            ->whereId($elementId)
            ->first();
        $this->assertIsObject($element);
        $this->assertEquals($price, $element->UF_PRICE);
        $this->assertEquals($labels, $element->UF_LABELS);
    }

    /**
     * Обновление элемента из модели
     * @return void
     * @throws \Bitrix\Main\SystemException
     */
    public function testUpdateElement()
    {
        $elementId = $this->createRandomElements()[0];
        $element = TestHighloadBlock::query()->whereId($elementId)->first();

        // Проверка через save
        $newName = Random::getString(10);
        $newLabels = $this->getRandomLabels();
        $newPrice = $this->getRandomPrice();
        $element->UF_NAME = $newName;
        $element->UF_PRICE = $newPrice;
        $element->UF_LABELS = $newLabels;
        $element->UF_ACTIVE_FROM = new DateTime();
        $update = $element->save();
        $this->assertTrue($update->isSuccess());
        $element->refresh();
        $this->assertEquals($newName, $element->UF_NAME);
        $this->assertEquals($newPrice, $element->UF_PRICE);
        $this->assertEquals($newLabels, $element->UF_LABELS);
        $this->assertInstanceOf(DateTime::class, $element->UF_ACTIVE_FROM);

        // Проверка через update
        $data = [
            'UF_NAME' => Random::getString(10),
            'UF_PRICE' => $this->getRandomPrice(),
            'UF_LABELS' => $this->getRandomLabels(),
        ];
        $element = TestHighloadBlock::query()->whereId($elementId)->first();
        $update = $element->update($data);
        $this->assertTrue($update->isSuccess());
        $element->refresh();
        $this->assertEquals($data['UF_NAME'], $element->UF_NAME);
        $this->assertEquals($data['UF_PRICE'], $element->UF_PRICE);
        $this->assertEquals($data['UF_LABELS'], $element->UF_LABELS);
    }

    /**
     * Удаление элемента из модели
     * @return void
     */
    public function testDeleteElement()
    {
        $elementId = $this->createRandomElements()[0];
        $element = TestHighloadBlock::query()->whereId($elementId)->first();
        $this->assertIsObject($element);
        $this->assertTrue($element->delete()->isSuccess());
        $this->assertNull(TestHighloadBlock::query()->whereId($elementId)->first());
    }

    /**
     * Создать случайные элементы
     * @param int $count
     * @param int|null $price
     * @param array|null $labels
     * @return int[]
     */
    private function createRandomElements(
        int    $count = 1,
        ?int   $price = null,
        ?array $labels = null,
    ): array
    {
        $elements = [];
        for ($i = 0; $i < $count; $i++) {
            // Случайные данные
            $price = $price ?: $this->getRandomPrice();
            $labels = $labels ?: $this->getRandomLabels();
            // Создадим элемент
            $element = new TestHighloadBlock();
            $element->UF_NAME = Random::getString(10);
            $element->UF_PRICE = $price;
            $element->UF_LABELS = $labels;
            $save = $element->save();
            if (! $save->isSuccess()) {
                $this->fail('Ошибка создания элемента');
            }
            $elements[] = $save->getId();
        }
        return $elements;
    }

    /**
     * @return int
     */
    private function getRandomPrice(): int
    {
        return rand(0, 100_00_00);
    }

    /**
     * @return array
     */
    private function getRandomLabels(): array
    {
        return [Random::getString(10), Random::getString(10), Random::getString(10)];
    }
}
