<?php

// Загрузка ядра BX
use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Pago\Bitrix\Models\Console\Generate\Models;
use Pago\Bitrix\Models\Helpers\IModelHelper;
use Pago\Bitrix\Models\Helpers\HlModelHelper;

if (empty($_SERVER['DOCUMENT_ROOT']) && php_sapi_name() === 'cli') {
    // Для vendor
    if (str_contains(getenv('PWD'), 'vendor')) {
        $_SERVER['DOCUMENT_ROOT'] = explode('vendor', getenv('PWD'))[0];
    } elseif (str_contains(getenv('PWD'), 'bitrix-models')) {
        // Возможно он помещен в коревую директорию bitrix-models
        $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../../';
    }
    if (str_ends_with($_SERVER['DOCUMENT_ROOT'], '/')) {
        $_SERVER['DOCUMENT_ROOT'] = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    }
}

if (! defined('B_PROLOG_INCLUDED'))
{
    include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
}

if (defined('B_PROLOG_INCLUDED')) {
    // Автозагрузка классов
    $pathModels = $_SERVER['DOCUMENT_ROOT'] . Models\Base::DEFAULT_MODEL_PATH;

    if (is_dir($pathModels)) {
        Loader::registerNamespace('Local\\Models', $pathModels);
    }
}

$eventManager = EventManager::getInstance();

/**
 * Инфоблоки
 */
// События на удаления инфоблока. Очищаем кэш модуля
$eventManager->addEventHandlerCompatible('iblock', 'OnIBlockDelete', function($id) {
    IModelHelper::clearIblockModelCache();
});
// События на создание инфоблока. Очищаем кэш модуля
$eventManager->addEventHandlerCompatible('iblock', 'OnAfterIBlockAdd', function($id) {
    IModelHelper::clearIblockModelCache();
});

/**
 * Highload
 */
// Событие на создание справочника. Очищаем кэш
$eventManager->addEventHandler('highloadblock', 'HighloadBlockOnAfterAdd', function() {
    HlModelHelper::clearHighloadModelCache();
});
// Событие на удаление справочника. Очищаем кэш
$eventManager->addEventHandler('highloadblock', 'HighloadBlockOnAfterDelete', function() {
    HlModelHelper::clearHighloadModelCache();
});