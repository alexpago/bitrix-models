# Bitrix модели (Таблицы, Инфоблоки, Highload)

Данный модуль позволяет легко обращаться к инфоблокам, Highload-блокам, таблицам в Bitrix CMS.

Текущий модуль не использует иных зависимостей и работает исключительно как фасет ядра D7.  

## Установка

1. ```composer require alexpago/bitrix-models```
2. Устанавливаем модуль
3. Подключаем автозагрузку моделей-классов при необходимости. Подробнее в разделе: [Автозагрузка моделей](#Автозагрузка-моделей)
4. Для удобства создания моделей создаем бинарный файл по пути bin/model. Содержимое файла:

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);
$_SERVER['DOCUMENT_ROOT'] = str_replace('/bin', '', getenv('PWD'));

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/alexpago/bitrix-models/src/Console/bin/generate';
```

## Обновление 1.2.0

> **Внимание:** Информация для обновления с версии 1.1.x (и старее) до 1.2.x

>### **Автозагрузка классов**
>При обновлении, если вы использовали автозагрузку моделей и используете модели по директории `local/lib/models/`, просьба обратить внимание на то, что для включения автозагрузки модулей теперь необходимо объявить константу `const AUTOLOAD_MODELS = true` перед подключением composer (желательно в `dbconn.php`) или подключить загрузку вручную.  
>Подробнее про автозагрузку классов в разделе: [Автозагрузка моделей](#Автозагрузка-моделей)
>
>### **Работа с инфоблоками**
>В связи с медленной работой загрузки свойств и некорректной работой множественных свойств была переработана логика получения свойств. В целом старая логика должна быть сохранена, но могут возникнуть проблемы "при переезде". В локальных проектах проблем не наблюдалось, но стоит обратить внимание.  
>На текущий момент работа с инфоблоками полностью работоспособна без дополнительных методов `getValue`, `getCollection`.  
>Для сохранения свойств из модели необходимо указать имя свойства без префикса `PROPERTY`. _Например:_  
>`$model->PRICE = 500; $model->save()`
>
>### **Скорость загрузки элементов инфоблоков**
>Загрузка свойств ускорена в 2 раза. При использовании параметра `->withCache()` скорость увеличивается до 4 раз.
>
>### **Генерация моделей**
>Весь класс логики был перемещен в `Builder`. Таким образом, при разработке больше не будут всплывать методы, которые не относятся к модели.  
>Для удобства разработки рекомендуем перегенерировать модели и переиспользовать phpdoc из новых моделей, применив к старым.
>
>### **Update, Save, Delete**
>Методы `update`, `save`, `delete` теперь разделены логикой и обрабатываются по-разному в модели и в построителе запроса.  
>_Например:_ Если это построитель запроса `->query()->where('ID', 5)->delete()`, то будет возвращен массив со всеми удаленными элементами `array<Bitrix\Main\ORM\Data\Result>`.  
>В случае удаления элемента из модели будет возвращен `Bitrix\Main\ORM\Data\Result`. Данная логика распространяется на методы: `update`, `delete`.  
>Обратите внимание, что старые методы `elementUpdate` и `elementDelete` помечены как устаревшие. Рекомендуется их не использовать и заменить на `update`, `delete`.
>
>### **Новые методы-операторы**
>Добавлены новые операторы. Подробнее можно почитать в разделе: [Доступные методы фильтрации](#Доступные-методы-фильтрации)
>
>### **Символьные коды инфоблоков**
>Ранее при некорректном символьном коде возникали ошибки создания модели. На текущий момент модель будет создана даже при некорректном названии с уведомлением о необходимости её переименования и рекомендацией действий.

## Создание моделей в автоматическом режиме

Для создания моделей **инфоблоков** выполните команду: `php bin/model iblock`. 

Для создания моделей **highload-блоков** выполните команду: `php bin/model hlblock`. 

Для создания моделей **таблиц** выполните команду: `php bin/model table`. 

**Путь создания моделей:** По умолчанию модели создаются в директории `local/lib/models` с namespace `Local\Models`. Для переопределения пути и namespace необходимо передать аргументы path и namespace соотвественно.
Например: `php bin/model table --path=/local/models/table/ --namespace=Local\Table`

В результате выполнения команды отобразится список highload-блоков или инфоблоков, в зависимости от типа модели. Для таблицы будет отображено поле ввода названия таблицы.
```text
Введите идентификаторы инфоблоков через пробел для генерации модели.
или all для всех. Для выхода введите "q". 
3 - Услуги CODE: services, API_CODE: services 
5 - Каталог: catalog, API_CODE: catalog 
Ввод : 
```

Выберите необходимые модели для генерации.

_Можно перечислить идентификаторы или названия таблиц прямо в команде через пробел:_
`php bin/model iblock 15 16` или `php bin/model table b_user`

**Внимание, для инфоблоков** в процессе создания модели будет автоматически заполнен API_CODE инфоблока **при его отсутствии**.

**Внимание, для инфоблоков** и работы модуля D7 необходим заполненный API_CODE у инфоблока.

После создания модели будет создан файл с примерным содержимым:

```php
<?php

namespace Local\Models\Iblock;

use Pago\Bitrix\Models\IModel;
use Pago\Bitrix\Models\Queries\Builder;

/**
 * @property array CATALOG_ITEMS // Привязка к основным продуктам
 * @property string PRICE // Стоимость
 * @method static Builder|$this query()
 * @method Builder|$this get()
 * @method Builder|$this first()
 * @method Builder|$this whereCatalogItems(mixed $data, string $operator = '') // Привязка к основным продуктам
 * @method Builder|$this wherePrice(mixed $data, string $operator = '') // Стоимость
 */
class Catalog extends IModel
{

}
```
## Создание моделей вручную

### Инфоблок:
Необходимо создать класс наследуясь от класса ```Pago\Bitrix\Models\IModel```
Название класса должно соответствовать символьному коду инфоблока в CamelSpace.

**Опционально:** Если необходимо, чтобы символьный код отличался от названия класса,
то необходимо заполнить константу `const IBLOCK_CODE` с указанием символьного кода инфоблока.

**Опционально 2:** Если идентификатор инфоблока является статическим на всех проектах,
то желательно указать его заполнив константу ```const IBLOCK_ID```.
Таким образом системе не нужно будет определять идентификатор инфоблока и система
сэкономит один SQL запрос.

### Highload-блок:
Необходимо создать класс наследуясь от класса ```Pago\Bitrix\Models\HlModel```
Название класса должно соответствовать коду справочника в CamelSpace.

**Опционально:** Если необходимо, чтобы символьный код отличался от названия класса,
то необходимо заполнить константу `const HL_CODE` с указанием символного кода справочника или `const HL_ID` с указанием ID справочника.

### Таблица:
Необходимо создать класс наследуясь от класса ```Pago\Bitrix\Models\TableModel```
Название класса должно соответствовать названию таблицы в CamelSpace.

**Опционально:** Если необходимо, чтобы название таблицы отличалось от названия модели, можно заполнить метод с названием таблицы
или указать константу `const TABLE_NAME`
```php
// Название таблицы через константу
const TABLE_NAME = 'b_hlblock_entity';
/**
 * Название таблицы через переопределение метода
 * @return string
 */
public static function getTableName(): string
{
    return 'b_user'; // Название таблицы
}
```

**Пример названия модели по символьному коду:** символьный код инфоблока/справочника `custom_catalog`, тогда название класса будет `CatalogModel`.

**Пример названия модели по названию таблицы:** таблица `b_option_sites` будет `BOptionSites`

**Пример готовой модели инфоблока:**

```php
<?php

namespace Local\Models\Iblock;

use Pago\Bitrix\Models\IModel;

class CatalogModel extends IModel
{
    const IBLOCK_CODE = 'custom_catalog';
}
```

## Автозагрузка моделей
Расположение моделей является сугубо Вашей фантазией. Если Вы не планируете писать большой код в проекте, то можно воспользоваться стандартными средствами загрузки классов моделей.
По умолчанию модели создаются в директории `local/lib/models` с namespace `Local\Models`. Они не подключаются автоматически. 
Для автоматического подключения необходимо объявить константу `const AUTOLOAD_MODELS = true` перед подключением composer (желательно в dbconn.php) или подключить загрузку вручную.
Для ручной загрузки необходимо вставить код до его использования, например в init.php

```php
Loader::registerNamespace('Local\\Models', $_SERVER['DOCUMENT_ROOT'] . '/local/lib/models');
```

## Получение элементов

Работа с классами аналогична работе с Bitrix D7 запросами.

### Базовый запрос
```php
$elements = CatalogModel::query()->withProperties()->get(); // get() вернет массив элементов
foreach ($elements as $element) {
    
}
```

### Базовый запрос с фильтрацией и лимитом
```php
CatalogModel::query()->setFilter(['CODE' => 'massage'])->setLimit(10)->get();
```
> Примечание: можно использовать сокращенный вариант установки лимита `->limit(10)` или передать первый параметр в `->get(10)`
> Например: ```CatalogModel::query()->get(10)```

> Заметка: для установки смещения `->setOffset(50)` или `->offset(50)` или передать значение вторым параметром в `->get(10, 50)`
> Например: `CatalogModel::query()->get(10, 50)`

### Поэтапное заполнение фильтра 

Метод `setFilter` устанавливает фильтр перезаписывая все ранее установленные условия. 

Взамен `setFilter` можно использовать `where(column, operator, value)`.

Для поиска OR после условия `where` можно использовать `orWhere(column, operator, value)`.

### Доступные методы фильтрации:
### `where(string $property, $operator, $data = null): static`
Фильтрация с условием для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства, по которому производится фильтрация.
- `$operator` (mixed): Оператор сравнения (например, '=', '>', '<', '!=', и т.д.).
- `$data` (mixed): Значение для сравнения с данным свойством. Если не указано, то используется оператор как значение.

---

### `whereIn(string $property, array $values): static`
Фильтрация с условием `IN` для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства.
- `$values` (array): Массив значений, которые должны быть проверены для этого свойства.

---

### `orWhereIn(string $property, array $values): static`
Фильтрация с условием `OR IN` для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства.
- `$values` (array): Массив значений, которые должны быть проверены для этого свойства.

---

### `whereProperty(string $property, string $property2): static`
Фильтрация, где свойство сравнивается с другим свойством.

**Параметры:**
- `$property` (string): Имя первого свойства.
- `$property2` (string): Имя второго свойства, с которым сравнивается первое.


---

### `orWhereProperty(string $property, string $property2): static`
Фильтрация с условием `OR`, где одно свойство сравнивается с другим.

**Параметры:**
- `$property` (string): Имя первого свойства.
- `$property2` (string): Имя второго свойства.

---

### `whereNotIn(string $property, array $values): static`
Фильтрация с условием `NOT IN` для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства.
- `$values` (array): Массив значений, которые не должны соответствовать данному свойству.

---

### `orWhere(string $property, $operator, $data = null): static`
Фильтрация с условием `OR` для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства.
- `$operator` (mixed): Оператор сравнения (например, '=', '>', '<', '!=', и т.д.).
- `$data` (mixed): Значение для сравнения с данным свойством. Если не указано, то используется оператор как значение.

---

### `whereNotNull(string $property): static`
Фильтрация по условию "не равно NULL" для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства.

---

### `whereNull(string $property): static`
Фильтрация по условию "равно NULL" для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства.

---

### `whereBetween(string $property, $min, $max): static`
Фильтрация с условием "между" для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства.
- `$min` (mixed): Минимальное значение диапазона.
- `$max` (mixed): Максимальное значение диапазона.

---

### `whereNotBetween(string $property, $min, $max): static`
Фильтрация с условием "не между" для указанного свойства.

**Параметры:**
- `$property` (string): Имя свойства.
- `$min` (mixed): Минимальное значение диапазона.
- `$max` (mixed): Максимальное значение диапазона.



Также существует упрощенный вариант фильтрации по полям `whereColumn(value, operator)`. 
Column должен быть заполнен в CamelSpace. Доступны все поля, включая свойства инфоблока. 
**Например:** Свойство с кодом `CITY_ID` можно отфильтровать как `whereCityId(value)`

> **Внимание:** operator по умолчанию **= (равно)**

> **Внимание:** если выполнить `setFilter` после `where` или `whereColumn`, то предыдущие значения будут стерты и учитываться будут только данные
> из `setFilter`

Пример фильтрации:
```php
CatalogModel::query()
    ->withProperties()
    ->whereCityId(1)
    ->orWhere('CITY_ID', 2)
    ->whereIblockSectionId(10)
    ->whereId(5, '>=') // по умолчанию всегда оператор = (равно), заполнять при необходимости указать другой
    ->get();
```

### Получение одного элемента
```php
CatalogModel::query()->whereId(100)->first(); // first() вернет экземпляр класса
```
> Если нужно получить элемент в виде массива, используйте `firstArray()`. Вызов метода `firstArray()` аналогичен цепочке
> вызовов `first()->toArray()`

### Выборка

Пример выборки с фильтрацией

```php
$products = Catalog::query()
    ->withProperties() // Прогрузить свойства инфоблоков
    ->wherePrice(200, '>=')
    ->whereBetween(
        property: 'DATE_CREATE',
        min: DateTime::tryeParse('01.01.2025', 'd.m.Y'),
        MAX: DateTime::tryeParse('01.03.2025', 'd.m.Y')
    )
    ->withCache()
    ->withDetailPageUrl() // Загрузить DETAIL_PAGE_URL для инфоблоков
    ->get();
$result = [];
foreach ($products as $product) {
    /**
     * @var Catalog $product 
     */
    $result[$product->ID] = [
        'PRICE' => $product->PRICE     
    ];
}
```

По умолчанию если не указывать `setSelect` будут выбраны все поля инфоблока **без свойств**. 
### Внимание:
**Для получения всех свойств инфоблока необходимо вызвать метод `withProperties`.** 

Пример:
```php
CatalogModel::query()->withProperties()->get();
```

> Если нужно получить элементы в виде массива, используйте `getArray()`. Вызов метода `getArray()` аналогичен цепочке
> вызовов `get()->toArray()`

Пример с заполнением выборки:
```php
// SALONS и CITY - свойства инфоблока. Обратите внимание, префикс PROPERTY указывать не нужно
CatalogModel::query()->setSelect(['ID', 'CODE', 'NAME', 'SALONS', 'CITY'])->get();
```

Пример с поэтапным пополнением выборки:
```php
CatalogModel::query()->select('ID')->select('CODE', 'NAME')->get();
```

Пример с поэтапным пополнением выборки и выгрузкой всех свойств:
```php
CatalogModel::query()->withProperties()->select('ID')->select('CODE', 'NAME')->get();
```

> **Внимание:** не рекомендуется выгружать все свойства без строгой необходимости
> **Внимание:** `DETAIL_PAGE_URL` не принадлежит элементам. Для получения используйте метод `getDetailPageUrl()`. 
> Если необходимо заранее получить список детальных страниц, перед get рекомендуем использовать `withDetailPageUrl()`

Пример с получением `detail page url`:
```php
$element = CatalogModel::query()->withDetailPageUrl()->select('ID')->first();
$element->getDetailPageUrl();
```

### Количество элементов

Пример получения количества элементов:
```php
CatalogModel::query()->where('ID', '>=', 1)->count();
```

### Сортировка

Сортировка происходит путем заполнение массивом через метод `setOrder` или путем наполнения `order(column)` и `orderDesc(column)`

Пример:
```php
CatalogModel::query()->setOrder(['ID' => 'ASC'])->get(); 
```

Пример заполнением:
```php
CatalogModel::query()->order('ID')->orderDesc('NAME')->get(); 
```

> **Внимание:** если выполнить `setOrder` после `order` или `orderDesc`, то предыдущие значения будут стерты и учитываться будут только данные
> из `setOrder`

### Кеширование

Запросы можно кешировать. Для этого используйте `withCache()`. Метод принимает два аргумента: время жизни
кэша в секундах и bool для кеширования join. Так же заранее можно предопределить в классе 
свойства `public int $cacheTtl` и `public bool $cacheJoin`.

```php
class CatalogModel extends IModel {
    public int $cacheTtl = 3600; // Кеш на 1 час
    public bool $cacheJoin = true;
}
```


Пример запроса с кешированием на час и кешированием join:
```php
$elements = CatalogModel::query()->withCache(3600, true)->get();
```

> Если в классе установлен кэш по умолчанию, то его можно отключить для определенного 
> запроса методом `withoutCache()`

### Преобразование результатов

По умолчанию данные возвращаются объектами ORM D7. 

Пример обработки запроса:
```php
$elements = CatalogModel::query()->withProperties()->get();
foreach ($elements as $element) {
    // Свойство SALONS множественное
    // Экземпляр класса Bitrix\Main\ORM\Objectify\Collection
    $salons = $element->getSalons();
    foreach ($salons as $salon) {
        $salon->getValue();
    }
    // Свойство CITY не множественное
    // Экземпляр класса Bitrix\Iblock\ORM\ValueStorage
    $city = $element->getCity()->getValue();
}
```

Так же можно приводить результат в массив

Пример:
```php
$elements = CatalogModel::query()->get();
foreach ($elements as $element) {
    $result = $element->toArray();
}
```

> **Внимание:** метод toArray вернет так же зависимости привязок. Например `IBLOCK_ELEMENT_ID`. 
> Для получения только значения `VALUE` используйте `$element->toArrayOnlyValues()`

## Действия

У моделей доступны быстрые действия.

### Удаление элемента

Пример массового удаления из query
```php
$products = Catalog::query()
    ->where(
        property: 'DATE_CREATE',
        operator: '<=',
        value: DateTime::tryParse('01.01.2025', 'd.m.Y')
    )
    ->delete();
// Результат <array>Bitrix\Main\ORM\Data\Result
foreach ($products as $products) {
    $success = $product->isSuccess();
    $data = $product->getData();
}
```

Любой элемент можно удалить одной командой

```php
$element = CatalogModel::query()->withProperties()->first();
// Результат Bitrix\Main\ORM\Data\Result
$element->delete();
```

Так же можно удалить элементы по фильтру не получая их экземпляры

```php
// Массив с результатом [Bitrix\Main\ORM\Data\Result]
$delete = CatalogModel::query()->withProperties()->whereActive(false)->delete();
```

### Обновление элемента

Обновление элемента происходит путем присваивания ему новых свойств через магический метод __set,
с последующим вызовом метода `save()`.

```php
$element = CatalogModel::query()->withProperties()->first();
$element->NAME = 'Новое имя';
$element->SALON_ID = 135; // Свойство инфоблока SALON_ID
// Результат сохранения Bitrix\Main\ORM\Data\Result
$element->save();
```

Так же можно воспользоваться методом `update()`

```php
$element = CatalogModel::query()->withProperties()->first();
// Result Bitrix\Main\ORM\Data\Result
$element->update([
    'NAME' => 'Новое имя'
]);
```

Метод `update()` можно применять аналогично `delete()` к `query` запросу

```php
$data = [
    'ACTIVE' => true
];
// Массив с результатом [Bitrix\Main\ORM\Data\Result]
$delete = CatalogModel::query()->withProperties()->whereActive(false)->update($data);
```

### Создание элемента

Создание нового элемента аналогично обновлению через `save()`. Для начала необходимо
создать экземпляр объекта и заполнить его данными. После заполнения вызвать метод `save()`.

```php
$element = new CatalogModel();
$element->NAME = 'Имя нового элемента';
// Результат сохранения Bitrix\Main\ORM\Data\Result
$element->save();
```

Пример редактирования моделей из запросов
```php
$products = Catalog::query()
    ->withProperties()
    ->withCache()
    ->withDetailPageUrl()
    ->get();
$result = [];
foreach ($products as $product) {
    /**
     * @var Catalog $product
     */
     $product->PRICE = $product->PRICE + 200;
     $product->save();
}
```

> Так же можно использовать метод `put()`, который вызовет метод `save()` и вернет экземпляр созданного объекта. 

### События

У моделей можно добавлять события на добавление, изменение и удаления элементов. 
Доступны следующие события: 

```php
/**
 * Событие вызываемое перед добавлением элементам
 * @return void
 */
protected function onBeforeAdd(): void
{
    // actions
}

/**
 * Событие вызываемое после добавление элемента
 * @param \Bitrix\Main\ORM\Data\Result $result
 * @return void
 */
protected function onAfterAdd(Result $result): void
{
    // actions
}

/**
 * Событие вызываемое перед обновлением элемента
 * @return void
 */
protected function onBeforeUpdate(): void
{
    // actions
}

/**
 * Событие вызываемое после обновления элемента
 * @param \Bitrix\Main\ORM\Data\Result $result
 * @return void
 */
protected function onAfterUpdate(Result $result): void
{
    // actions
}

/**
 * Событие вызываемое перед удалением элемента
 * @return void
 */
protected function onBeforeDelete(): void
{
    // actions
}

/**
 * Событие вызываемое после удаления элемента
 * @param \Bitrix\Main\ORM\Data\Result $result
 * @return void
 */
protected function onAfterDelete(Result $result): void
{
    // actions
}
```

> Для получения списка изменяемых свойств можно использовать метод `getChangedProperties`

Пример:
```php
protected function onBeforeUpdate(): void
{
    $data = $this->getProperties(); // Текущие свойства
    $originalData = $this->getOriginalProperties(); // Свойства при инициализации модели
    $changedData = $this->getChangedProperties(); // Измененные свойства
    // Можно предопределить данные
    $this->NAME .= ' Обновлено';
}
```