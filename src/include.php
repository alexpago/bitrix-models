<?php

// Загрузка ядра BX
use Bitrix\Main\Loader;
use Pago\Bitrix\Models\Console\Generate\Models;

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