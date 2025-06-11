<?php

namespace Pago\Bitrix\Tests\Models;

use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use Pago\Bitrix\Tests\Resources\Models\TestTable;
use Pago\Bitrix\Tests\Resources\Models\TestTable2;
use PHPUnit\Framework\TestCase;

/**
 * Тестирование моделей справочника
 */
final class TableTest extends TestCase
{
    // Название таблицы тестового справочника
    public const TABLE_NAME = 'test_table';

    // Запрос на создание таблицы test_table
    private const SQL_CREATE = 'CREATE TABLE test_table (
  ID int unsigned NOT NULL AUTO_INCREMENT,
  NAME varchar(255),
  XML_ID varchar(255),
  PRICE double DEFAULT NULL,
  ACTIVE_FROM datetime DEFAULT NULL,
  PRIMARY KEY (ID)
) ENGINE=InnoDB;';

    // Запрос на создание таблицы test_table_2
    private const SQL_CREATE_2 = 'CREATE TABLE test_table_2 (
  ID int unsigned NOT NULL AUTO_INCREMENT,
  FIELD varchar(255),
  PRIMARY KEY (ID)
) ENGINE=InnoDB;';

    // Запрос на удаление таблицы test_table
    private const SQL_DELETE = 'drop table if exists test_table;';

    // Запрос на удаление таблицы test_table_2
    private const SQL_DELETE_2 = 'drop table if exists test_table_2;';

    /**
     * @return void
     * @throws \Exception
     */
    public static function setUpBeforeClass(): void
    {
        global $DB;
        $DB->Query(self::SQL_DELETE);
        $DB->Query(self::SQL_DELETE_2);
        $DB->Query(self::SQL_CREATE);
        $DB->Query(self::SQL_CREATE_2);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function tearDownAfterClass(): void
    {
        global $DB;
        $DB->Query(self::SQL_DELETE);
        $DB->Query(self::SQL_DELETE_2);
    }

    /**
     * Добавление элемента из запроса
     * @return void
     */
    public function testQueryAddElement()
    {
        // Добавим элементы в TestTable
        $data = [
            'NAME' => Random::getString(10),
            'PRICE' => $this->getRandomPrice(),
        ];
        $add = TestTable::query()->add($data);
        $this->assertTrue($add->isSuccess());
        $element = TestTable::query()
            ->withProperties()
            ->whereId($add->getId())
            ->first();
        $this->assertNotEmpty($element);
        if ($element) {
            $this->assertEquals($element->ID, $add->getId());
            $this->assertEquals($data['NAME'], $element->NAME);
            $this->assertEquals($data['PRICE'], $element->PRICE);
        }
        // Добавим элементы в TestTable2
        $add = TestTable2::query()->add([
            'FIELD' => Random::getString(10)
        ]);
        $this->assertTrue($add->isSuccess());
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
                'NAME' => Random::getString(10),
                'PRICE' => $this->getRandomPrice(),
                'XML_ID' => Random::getString(10)
            ];
        }
        // Вставляем их через query
        $elementIds = [];
        foreach (TestTable::query()->insert($insertElements) as $result) {
            $elementIds[] = $result->getId();
            $this->assertTrue($result->isSuccess());
        }
        // Проверяем созданные элементы
        $this->assertSameSize(
            $insertElements,
            TestTable::query()->whereId($elementIds)->get()
        );
        // Проверяем созданные элементы по содержимому
        $this->assertSameSize(
            $insertElements,
            TestTable::query()->whereXmlId(array_column($insertElements, 'XML_ID'))->get()
        );
        $this->assertSameSize(
            $insertElements,
            TestTable::query()
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
        $countBefore = TestTable::query()->count();
        // Удалим 3 элемента из ранее созданных
        TestTable::query()
            ->whereId(array_slice($elements, 0, 3))
            ->delete();
        // Посчитаем количество элементов сейчас
        $this->assertEquals($countBefore - 3, TestTable::query()->count());
        // Посчитаем количество по ранее созданным элементам
        $this->assertEquals(7, TestTable::query()->whereId($elements)->count());
    }

    /**
     * Добавление элемента из модели
     * @return void
     */
    public function testAddElement()
    {
        $price = 1000;
        // Создадим случайный элемент
        $elementId = $this->createRandomElements(
            price: $price
        )[0];
        // Проверим существование элемента
        $element = TestTable::query()
            ->withProperties()
            ->whereId($elementId)
            ->first();
        $this->assertIsObject($element);
        $this->assertEquals($price, $element->PRICE);
    }

    /**
     * Обновление элемента из модели
     * @return void
     * @throws \Bitrix\Main\SystemException
     */
    public function testUpdateElement()
    {
        $elementId = $this->createRandomElements()[0];
        $element = TestTable::query()->whereId($elementId)->first();

        // Проверка через save
        $newName = Random::getString(10);
        $newPrice = $this->getRandomPrice();
        $element->NAME = $newName;
        $element->PRICE = $newPrice;
        $element->ACTIVE_FROM = new DateTime();
        $update = $element->save();
        $this->assertTrue($update->isSuccess());
        $element->refresh();
        $this->assertEquals($newName, $element->NAME);
        $this->assertEquals($newPrice, $element->PRICE);
        $this->assertInstanceOf(DateTime::class, $element->ACTIVE_FROM);

        // Проверка через update
        $data = [
            'NAME' => Random::getString(10),
            'PRICE' => $this->getRandomPrice(),
        ];
        $element = TestTable::query()->whereId($elementId)->first();
        $update = $element->update($data);
        $this->assertTrue($update->isSuccess());
        $element->refresh();
        $this->assertEquals($data['NAME'], $element->NAME);
        $this->assertEquals($data['PRICE'], $element->PRICE);
    }

    /**
     * Удаление элемента из модели
     * @return void
     */
    public function testDeleteElement()
    {
        $elementId = $this->createRandomElements()[0];
        $element = TestTable::query()->whereId($elementId)->first();
        $this->assertIsObject($element);
        $this->assertTrue($element->delete()->isSuccess());
        $this->assertNull(TestTable::query()->whereId($elementId)->first());
    }

    /**
     * Создать случайные элементы
     * @param int $count
     * @param int|null $price
     * @return int[]
     */
    private function createRandomElements(
        int  $count = 1,
        ?int $price = null,
    ): array
    {
        $elements = [];
        for ($i = 0; $i < $count; $i++) {
            // Случайные данные
            $price = $price ?: $this->getRandomPrice();
            // Создадим элемент
            $element = new TestTable();
            $element->NAME = Random::getString(10);
            $element->PRICE = $price;
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
}
