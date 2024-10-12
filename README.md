# Bitrix models

Данный модуль позволяет легко обращаться к инфоблокам, Highload-блокам, таблицам в Bitrix CMS.

Текущий модуль не использует иных зависимостей и работает исключительно как фасет ядра D7.  

## Установка

1. ```composer require alexpago/bitrix-models```
2. Устанавливаем модуль
3. Для удобства создания моделей создаем бинарный файл по пути bin/model. Содержимое файла:

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);
$_SERVER['DOCUMENT_ROOT'] = str_replace('/bin', '', getenv('PWD'));

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/alexpago/bitrix-models/src/Console/bin/generate';
```

## Создание моделей в автоматическом режиме

Для создания моделей инфоблоков выполняем команду ```php bin/model iblock```. 
В результате выполнения команды отобразится список инфоблоков:

```text
Введите идентификаторы инфоблоков через пробел для генерации модели.
или all для всех. Для выхода введите "q". 
3 - Услуги CODE: services, API_CODE: services 
5 - Сертификаты на услугу CODE: certificates_services, API_CODE: certificates_services 
9 - Подарочные карты (Депозиты) CODE: deposits, API_CODE: - 
Ввод : 
```

Выберите необходимые модели для генерации. 
В процессе создания модели будет автоматически заполнен API_CODE инфоблока при его отсутствии.
**Внимание:** для модуля D7 необходим заполненный API_CODE у инфоблока.

После создания модели будет создан файл с примерным содержимым:

```php
<?php

namespace Pago\Bitrix\Models\Models\Catalog;

use Bitrix\Iblock\ORM\ValueStorage;
use Bitrix\Main\ORM\Objectify\Collection;
use Pago\Bitrix\Models\IModel;

/**
 * Для вызова методов getProperty и получения значения используйте метод getValue()
 * @method ValueStorage getCml2Service() // Привязка к услуге
 * @method Collection getIncludedInPrice() // Что входит в стоимость
 * @method ValueStorage getAppName() // Название (для приложения)
 * @method Collection getSalons() // Салоны
 * @method $this whereCml2Service(mixed $data, string $operator = '') // Привязка к услуге
 * @method $this whereIncludedInPrice(mixed $data, string $operator = '') // Что входит в стоимость
 * @method $this whereAppName(mixed $data, string $operator = '') // Название (для приложения)
 * @method $this whereSalons(mixed $data, string $operator = '') // Салоны
 */
class CertificatesServices extends IModel
{

}
```
## Создание моделей в ручном режиме

**Инфоблок:** Необходимо создать класс наследуясь от класса ```Pago\Bitrix\Models\IModel```

Название класса должно соответствовать символьному коду инфоблока в camelSpace. 

**Пример:** символьный код инфоблока certificates_services, тогда название класса будет CertificatesServices

**Опционально:** Если необходимо, чтобы символьный код отличался от названия класса, 
то необходимо заполнить константу ```const IBLOCK_CODE``` с указанием символьного кода инфоблока.

**Пример:**

```php
<?php

namespace Pago\Bitrix\Models\Models\Catalog;

use Pago\Bitrix\Models\IModel;

class CertificatesServicesModel extends IModel
{
    const IBLOCK_CODE = 'certificates_services';
}
```

**Опционально:** Если идентификатор инфоблока является статическим на всех проектах, 
то желательно указать его заполнив константу ```const IBLOCK_ID```. 
Таким образом системе не нужно будет определять идентификатор инфоблока и система 
сэкономит один SQL запрос.
**Пример:**

```php
<?php

namespace Pago\Bitrix\Models\Models\Catalog;

use Pago\Bitrix\Models\IModel;

class CertificatesServicesModel extends IModel
{
    const IBLOCK_ID = 3;
}
```

## Получение элементов

Работа с классами аналогична работе с Bitrix D7 запросами.

### Базовый запрос
```php
$elements = CertificatesServices::query()->withProperties()->get(); // get() вернет массив элементов
foreach ($elements as $element) {
    
}
```

### Базовый запрос с фильтрацией и лимитом
```php
CertificatesServices::query()->setFilter(['CODE' => 'massage'])->setLimit(10)->get();
```
> Заметка: можно использовать сокращенный вариант установки лимита `->limit(10)` или передать первый параметр в `->get(10)`
> Например: ```CertificatesServices::query()->get(10)```

> Заметка: для установки смещения `->setOffset(50)` или `->offset(50)` или передать значение вторым параметром в `->get(10, 50)`
> Например: `CertificatesServices::query()->get(10, 50)`

### Поэтапное заполнение фильтра 

Метод `setFilter` устанавливает фильтр сбрасывая предыдущие условия, если они были установлены ранее. 

Взамен `setFilter` можно использовать `where(column, operator, value)`.

Для поиска OR после условия `where` можно использовать `orWhere(column, operator, value)`.


Так же существует упрощенный вариант фильтрации по полям `whereColumn(value, operator)`. 
Column должен быть заполнен в CamelSpace. Доступны все поля, включая свойства инфоблока. 
**Например:** Свойство с кодом `CITY_ID` можно отфильтровать как `whereCityId(value)`

> **Внимание:** operator по умолчанию **= (равно)**

> **Внимание:** если выполнить `setFilter` после `where` или `whereColumn`, то предыдущие значения будут стерты и учитываться будут только данные
> из `setFilter`

Пример фильтрации:
```php
CertificatesServices::query()
    ->withProperties()
    ->whereCityId(1)
    ->orWhere('CITY_ID', 2)
    ->whereIblockSectionId(10)
    ->whereId(5, '>=') // по умолчанию всегда оператор = (равно), заполнять при необходимости указать другой
    ->get();
```

### Получение одного элемента
```php
CertificatesServices::query()->whereId(100)->first(); // first() вернет экземпляр класса
```
> Если нужно получить элемент в виде массива, используйте `firstArray()`. Вызов метода `firstArray()` аналогичен цепочке
> вызовов `first()->toArray()`

### Выборка

По умолчанию если не указывать `setSelect` будут выбраны все поля инфоблока **без свойств**. 
Для получения всех свойств необходимо вызвать метод `withProperties`. 

Пример:
```php
CertificatesServices::query()->withProperties()->get();
```

> Если нужно получить элементы в виде массива, используйте `getArray()`. Вызов метода `getArray()` аналогичен цепочке
> вызовов `get()->toArray()`

Пример с заполнением выборки:
```php
// SALONS и CITY - свойства инфоблока. Обратите внимание, префикс PROPERTY указывать не нужно
CertificatesServices::query()->setSelect(['ID', 'CODE', 'NAME', 'SALONS', 'CITY'])->get();
```

Пример с поэтапным пополнением выборки:
```php
// SALONS и CITY - свойства инфоблока. Обратите внимание, префикс PROPERTY указывать не нужно
CertificatesServices::query()->select('ID')->select('CODE', 'NAME')->get();
```

Пример с поэтапным пополнением выборки и выгрузкой всех свойств:
```php
// SALONS и CITY - свойства инфоблока. Обратите внимание, префикс PROPERTY указывать не нужно
CertificatesServices::query()->withProperties()->select('ID')->select('CODE', 'NAME')->get();
```

> **Внимание:** не рекомендуется выгружать все свойства без строгой необходимости
> **Внимание:** `DETAIL_PAGE_URL` не принадлежит элементам. Для получения используйте метод `getDetailPageUrl()`. 
> Если необходимо заранее получить список детальных страниц, перед get рекомендуем использовать `withDetailPageUrl()`

Пример с получением `detail page url`:
```php
// SALONS и CITY - свойства инфоблока. Обратите внимание, префикс PROPERTY указывать не нужно
$element = CertificatesServices::query()->withDetailPageUrl()->select('ID')->first();
$element->getDetailPageUrl();
```

### Количество элементов

Пример получения количества элементов:
```php
// SALONS и CITY - свойства инфоблока. Обратите внимание, префикс PROPERTY указывать не нужно
CertificatesServices::query()->where('ID', '>=', 1)->count();
```

### Сортировка

Сортировка происходит путем заполнение массивом через метод `setOrder` или путем наполнения `order(column)` и `orderDesc(column)`

Пример:
```php
CertificatesServices::query()->setOrder(['ID' => 'ASC'])->get(); 
```

Пример заполнением:
```php
CertificatesServices::query()->order('ID')->orderDesc('NAME')->get(); 
```

> **Внимание:** если выполнить `setOrder` после `order` или `orderDesc`, то предыдущие значения будут стерты и учитываться будут только данные
> из `setOrder`

### Кеширование

Запросы можно кешировать. Для этого используйте `withCache()`. Метод принимает два аргумента: время жизни
кэша в секундах и bool для кеширования join. Так же заранее можно предопределить в классе 
свойства `public int $cacheTtl` и `public bool $cacheJoin`.

Пример запроса с кешированием на час и кешированием join:
```php
$elements = CertificatesServices::query()->withCache(3600, true)->get();
```

> Если в классе установлен кэш по умолчанию, то его можно отключить для определенного 
> запроса методом `withoutCache()`

### Преобразование результатов

По умолчанию данные возвращаются объектами ORM D7. 

Пример обработки запроса:
```php
$elements = CertificatesServices::query()->withProperties()->get();
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
$elements = CertificatesServices::query()->get();
foreach ($elements as $element) {
    $result = $element->toArray();
}
```

> **Внимание:** метод toArray вернет так же зависимости привязок. Например `IBLOCK_ELEMENT_ID`. 
> Для получения только значения `VALUE` используйте `$element->toArrayOnlyValues()`

## Действия

У моделей доступны быстрые действия.

### Удаление элемента 

Любой элемент можно удалить одной командой

```php
$element = CertificatesServices::query()->withProperties()->first();
// Массив с результатом [Bitrix\Main\ORM\Data\Result]
$element->delete();
// Для получения результата в bool
$element->elementDelete();
```

Так же можно удалить элементы по фильтру не получая их экземпляры

```php
// Массив с результатом [Bitrix\Main\ORM\Data\Result]
$delete = CertificatesServices::query()->withProperties()->whereActive(false)->delete();
```

### Обновление элемента

Обновление элемента происходит путем присваивания ему новых свойств через магический метод __set,
с последующим вызовом метода `save()`.

```php
$element = CertificatesServices::query()->withProperties()->first();
$element->NAME = 'Новое имя';
$element->SALON_ID = 135; // Свойство инфоблока SALON_ID
// Результат сохранения Bitrix\Main\ORM\Data\Result
$element->save();
```

Так же можно воспользоваться методом `update()`

```php
$element = CertificatesServices::query()->withProperties()->first();
// Массив с результатом [Bitrix\Main\ORM\Data\Result]
$element->update([
    'NAME' => 'Новое имя'
]);
// Результат запроса Bitrix\Main\ORM\Data\Result
$element->elementUpdate([
    'NAME' => 'Новое имя 2'
]);
```

Метод `update()` можно применять аналогично `delete()` к `query` запросу

```php
$data = [
    'ACTIVE' => true
];
// Массив с результатом [Bitrix\Main\ORM\Data\Result]
$delete = CertificatesServices::query()->withProperties()->whereActive(false)->update($data);
```

### Создание элемента

Создание нового элемента аналогично обновлению через `save()`. Для начала необходимо
создать экземпляр объекта и заполнить его данными. После заполнения вызвать метод `save()`.

```php
$element = new CertificatesServices();
$element->NAME = 'Имя нового элемента';
// Результат сохранения Bitrix\Main\ORM\Data\Result
$element->save();
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