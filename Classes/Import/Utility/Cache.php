<?php

namespace BrainAppeal\CampusEventsConnector\Import\Utility;

use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Cache utility class
 */
class Cache
{
    /**
     * Retrieves the cache data collector from the current request.
     *
     * The cache data collector is obtained from the TYPO3 front-end request attributes.
     *
     * @return CacheDataCollector The cache data collector instance.
     */
    protected static function getCacheDataCollector(): CacheDataCollector
    {
        return $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.cache.collector');
    }

    /**
     * Adds cache tags to the cache data collector.
     *
     * This method processes an array of cache tags and adds each tag
     * to the cache data collector, ensuring that the tags are properly stored
     * for caching purposes.
     *
     * @param string[] $cacheTagIdentifiers Array of cache tag names to be added
     */
    public static function addCacheTags(array $cacheTagIdentifiers): void
    {
        if (count($cacheTagIdentifiers) > 0) {
            $cacheDataCollector = self::getCacheDataCollector();
            $cacheTags = [];
            foreach ($cacheTagIdentifiers as $cacheTagIdentifier) {
                $cacheTags[] = new CacheTag($cacheTagIdentifier);
            }
            $cacheDataCollector->addCacheTags(...$cacheTags);
        }
    }

    /**
     * Clears the specified cache tags by flushing the caches associated
     * with the provided tags in the given cache group.
     *
     * @param array $cacheTags An array of cache tags to be cleared.
     */
    public static function clearCacheTags(array $cacheTags): void
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        //$cacheManager->flushCachesByTags($cacheTags);
        $cacheManager->flushCachesInGroupByTags('pages', $cacheTags);
        //        foreach ($cacheTags as $cacheTag) {
        //            /** @var DataHandler $dataHandler */
        //            // Do not inject or reuse the DataHandler as it holds state!
        //            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        //            $dataHandler->start([], []);
        //            $dataHandler->clear_cacheCmd($cacheTag);
        //        }
    }

    /**
     * Adds cache tags to the page cache by records.
     *
     * The following cache tags will be added to cache collector: "tx_campus_events_connector_CLASS_uid_[model:uid]"
     *
     * @param string $table
     * @param AbstractEntity[]|QueryResult $records with records
     */
    public static function addCacheTagsByRecords(string $table, array|QueryResult $records): void
    {
        $cacheTags = [];
        $cacheGroup = str_replace('_domain_model', '', $table);
        foreach ($records as $model) {
            // cache tag for each model
            $cacheTags[] = $cacheGroup . '_' . $model->getUid();
        }
        self::addCacheTags($cacheTags);
    }

    /**
     * Clears the default cache tag by utilizing the configured tag prefix.
     * @param string $tagPrefix
     */
    public static function clearDefaultCacheTag(string $tagPrefix): void
    {
        self::clearCacheTags([$tagPrefix]);
    }

    /**
     * Delete the cache entries for each of the given source data types
     * @param string $table
     * @param array<int|string> $recordIdList
     */
    public static function clearRecordCaches(string $table, array $recordIdList): void
    {
        $clearCacheTags = [];
        $cacheGroup = str_replace('_domain_model', '', $table);
        foreach ($recordIdList as $recordId) {
            $clearCacheTags[] = $cacheGroup . '_' . $recordId;
        }
        self::clearCacheTags($clearCacheTags);
    }
}
