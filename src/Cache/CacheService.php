<?php

namespace Pago\Bitrix\Models\Cache;

use Bitrix\Main\Application;
use Pago\Bitrix\Models\HlModel;
use Pago\Bitrix\Models\Queries\Builder;
use Pago\Bitrix\Models\TableModel;
use Bitrix\Main\Data\Cache;

/**
 * Сервис кэширования
 */
class CacheService
{
    // Путь до кэширования
    public const CACHE_PATH = 'models';

    /**
     * @var CacheService|null
     */
    private static ?self $instance = null;

    /**
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param Builder $builder
     * @param int $ttl
     * @return array|null
     */
    public function getCache(Builder $builder, int $ttl = 0): ?array
    {
        if (! $ttl) {
            return null;
        }
        $cacheKey = md5(serialize($builder));
        $cache = Cache::createInstance(); // Служба кеширования
        if ($cache->initCache($ttl, $cacheKey, $this::CACHE_PATH)) {
            return $cache->getVars() ?: null;
        }
        return null;
    }

    /**
     * @param Builder $builder
     * @param array $data
     * @param int $ttl
     * @return void
     */
    public function setCache(Builder $builder, array $data, int $ttl = 0): void
    {
        if (! $ttl) {
            return;
        }
        $cacheKey = md5(serialize($builder));
        $type = 'iblock';
        if ($builder->getModel() instanceof HlModel) {
            $type = 'highload';
        } elseif ($builder->getModel() instanceof TableModel) {
            $type = 'table';
        }
        $cache = Cache::createInstance(); // Служба кеширования
        $cache->initCache($ttl, $cacheKey, self::CACHE_PATH);
        $taggedCache = Application::getInstance()->getTaggedCache(); // Служба пометки кеша тегами
        if (! $cache->startDataCache()) {
            return;
        }
        // Добавляем теги кэша
        $taggedCache->startTagCache(self::CACHE_PATH);
        $taggedCache->registerTag($type . '_id_1');
        $taggedCache->endTagCache();
        $cache->endDataCache($data);
    }

    /**
     * Получить кэш для инфоблока по ключу
     * @param int|array $iblockId
     * @param string $cacheKey
     * @param int $ttl
     * @return array|null
     */
    public function getIblockCache(
        int|array $iblockId,
        string    $cacheKey,
        int       $ttl = 0
    ): ?array
    {
        if (! $ttl) {
            return null;
        }
        $cache = Cache::createInstance(); // Служба кеширования
        if ($cache->initCache($ttl, $cacheKey, $this::CACHE_PATH)) {
            return $cache->getVars() ?: null;
        }
        return null;
    }

    /**
     * Установка кэша для инфоблока
     * @param int|array $iblockId
     * @param string $cacheKey
     * @param array $data
     * @param int $ttl
     * @return void
     */
    public function setIblockCache(
        int|array $iblockId,
        string    $cacheKey,
        array     $data,
        int       $ttl = 0
    ): void
    {
        if (! $ttl) {
            return;
        }
        $cache = Cache::createInstance(); // Служба кеширования
        $cache->initCache($ttl, $cacheKey, self::CACHE_PATH);
        $taggedCache = Application::getInstance()->getTaggedCache(); // Служба пометки кеша тегами
        if (! $cache->startDataCache()) {
            return;
        }
        // Добавляем теги кэша
        $taggedCache->startTagCache(self::CACHE_PATH);
        foreach ((array)$iblockId as $id) {
            $taggedCache->registerTag('iblock_id_' . $id);

        }
        $taggedCache->endTagCache();
        $cache->endDataCache($data);
    }
}
