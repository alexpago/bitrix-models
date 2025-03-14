<?php

namespace Pago\Bitrix\Tests\Models;

use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Tests\Helpers\IBlockCreatorHelper;
use Pago\Bitrix\Tests\Helpers\IBlockDeleteHelper;
use Pago\Bitrix\Tests\Resources\Models\TestIblockModel;
use PHPUnit\Framework\TestCase;
use CModule;

/**
 * Тестирование моделей инфоблока
 */
final class IblockTest extends TestCase
{
    // Код тестового инфоблока
    public const IBLOCK_CODE = 'test_iblock_model';

    /**
     * @var int|null
     */
    public static ?int $iblockId = null;

    /**
     * @return void
     * @throws \Exception
     */
    public static function setUpBeforeClass(): void
    {
        CModule::IncludeModule('iblock');

        // Удалим старый инфоблок при наличии
        $oldIblockId = IModelHelper::getIblockIdByCode(self::IBLOCK_CODE);
        if ($oldIblockId) {
            IModelHelper::clearIblockModelCache();
            IBlockDeleteHelper::deleteIblockWithDependencies($oldIblockId);
        }

        // Используем вспомогательный класс для создания инфоблока
        self::$iblockId = IBlockCreatorHelper::createIblock(
            name: 'TestIblock',
            code: self::IBLOCK_CODE,
            type: IBlockCreatorHelper::getRandomType(),
            apiCode: Random::getStringByAlphabet(10, Random::ALPHABET_ALPHAUPPER)
        ) ?: null;
        if (!is_int(self::$iblockId)) {
            self::fail('Ошибка создания инфоблока');

        }
        // Добавление свойств
        $properties = [
            [
                'NAME' => 'Цена',
                'CODE' => 'PRICE',
                'PROPERTY_TYPE' => 'N', // Число
            ],
            [
                'MULTIPLE' => 'Y',
                'NAME' => 'Лейблы',
                'CODE' => 'LABELS',
                'PROPERTY_TYPE' => 'S', // Строка
            ]
        ];

        IBlockCreatorHelper::addProperties(self::$iblockId, $properties);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function tearDownAfterClass(): void
    {
        IBlockDeleteHelper::deleteIblockWithDependencies(self::$iblockId);
    }

    /**
     * Добавление элемента из запроса
     * @return void
     */
    public function testQueryAddElement()
    {
        $data = [
            'NAME' => Random::getString(10),
            'PRICE' => $this->getRandomPrice(),
            'LABELS' => $this->getRandomLabels(),
        ];
        $add = TestIblockModel::query()->add($data);
        $this->assertTrue($add->isSuccess());
        $element = TestIblockModel::query()
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
        for ($i = 0; $i < rand(1, 10); $i++) {
            $insertElements[] = [
                'NAME' => Random::getString(10),
                'PRICE' => $this->getRandomPrice(),
                'LABELS' => $this->getRandomLabels(),
                'XML_ID' => Random::getString(10),
            ];
        }
        // Вставляем их через query
        $elementIds = [];
        foreach (TestIblockModel::query()->insert($insertElements) as $result) {
            $elementIds[] = $result->getId();
            $this->assertTrue($result->isSuccess());
        }
        // Проверяем созданные элементы
        $this->assertSameSize(
            $insertElements,
            TestIblockModel::query()->whereId($elementIds)->get()
        );
        // Проверяем созданные элементы по содержимому
        $this->assertSameSize(
            $insertElements,
            TestIblockModel::query()->whereXmlId(array_column($insertElements, 'XML_ID'))->get()
        );
        $this->assertSameSize(
            $insertElements,
            TestIblockModel::query()
                ->whereId($elementIds)
                ->wherePrice(array_column($insertElements, 'PRICE'))
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
        $countBefore = TestIblockModel::query()->count();
        // Удалим 3 элемента из ранее созданных
        TestIblockModel::query()
            ->whereId(array_slice($elements, 0, 3))
            ->delete();
        // Посчитаем количество элементов сейчас
        $this->assertEquals($countBefore - 3, TestIblockModel::query()->count());
        // Посчитаем количество по ранее созданным элементам
        $this->assertEquals(7, TestIblockModel::query()->whereId($elements)->count());
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
        $element = TestIblockModel::query()
            ->withProperties()
            ->whereId($elementId)
            ->first();
        $this->assertIsObject($element);
        $this->assertEquals($price, $element->PRICE);
        $this->assertEquals($labels, $element->LABELS);
    }

    /**
     * Обновление элемента из модели
     * @return void
     */
    public function testUpdateElement()
    {
        $elementId = $this->createRandomElements()[0];
        $element = TestIblockModel::query()->whereId($elementId)->first();

        // Проверка через save
        $newName = Random::getString(10);
        $newLabels = $this->getRandomLabels();
        $newPrice = $this->getRandomPrice();
        $element->NAME = $newName;
        $element->PRICE = $newPrice;
        $element->LABELS = $newLabels;
        $element->ACTIVE_FROM = new DateTime();
        $update = $element->save();
        $this->assertTrue($update->isSuccess());
        $element->refresh();
        $this->assertEquals($newName, $element->NAME);
        $this->assertEquals($newPrice, $element->PRICE);
        $this->assertEquals($newLabels, $element->LABELS);
        $this->assertNotEmpty($element->ACTIVE_FROM);

        // Проверка через update
        $data = [
            'NAME' => Random::getString(10),
            'PRICE' => $this->getRandomPrice(),
            'LABELS' => $this->getRandomLabels(),
        ];
        $element = TestIblockModel::query()->whereId($elementId)->first();
        $update = $element->update($data);
        $this->assertTrue($update->isSuccess());
        $element->refresh();
        $this->assertEquals($data['NAME'], $element->NAME);
        $this->assertEquals($data['PRICE'], $element->PRICE);
        $this->assertEquals($data['LABELS'], $element->LABELS);
    }

    /**
     * Удаление элемента из модели
     * @return void
     */
    public function testDeleteElement()
    {
        $elementId = $this->createRandomElements()[0];
        $element = TestIblockModel::query()->whereId($elementId)->first();
        $this->assertIsObject($element);
        $this->assertTrue($element->delete()->isSuccess());
        $this->assertNull(TestIblockModel::query()->whereId($elementId)->first());
    }

    /**
     * Получение элемента
     * @return void
     */
    public function testGetElement()
    {
        $elementId = $this->createRandomElements()[0];
        $element = TestIblockModel::query()
            ->whereId($elementId)
            ->first();
        $this->assertIsObject($element);
        $this->assertNull($element->PRICE);
        $this->assertNull($element->LABELS);

        $element = TestIblockModel::query()
            ->select('ID', 'PRICE')
            ->whereId($elementId)
            ->first();
        $this->assertNotNull($element->PRICE);
        $this->assertNotNull($element->ID);
        $this->assertNull($element->LABELS);

        $element = TestIblockModel::query()
            ->withProperties()
            ->withDetailPageUrl()
            ->select('ID, LABELS')
            ->whereId($elementId)
            ->first();
        $this->assertNotNull($element->LABELS);
        $this->assertNotNull($element->ID);
        $this->assertNull($element->PRICE);
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
            $element = new TestIblockModel();
            $element->NAME = Random::getString(10);
            $element->PRICE = $price;
            $element->LABELS = $labels;
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
