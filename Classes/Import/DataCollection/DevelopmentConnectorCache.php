<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataCollection;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DevelopmentConnectorCache
{
    /**
     * The number of days for which the local cache is considered valid in a development environment.
     */
    private int $developmentLocalCacheAgeInDays = 0;

    private string $relativePath;

    public function __construct(
        private readonly LoggerInterface $logger,
        string          $relativePath,
        int                              $developmentLocalCacheAgeInDays = 0
    ) {
        $this->relativePath = trim($relativePath, '/');
        if ($developmentLocalCacheAgeInDays > 0 && Environment::getContext()->isDevelopment()) {
            $this->developmentLocalCacheAgeInDays = $developmentLocalCacheAgeInDays;
        }
    }

    /**
     * Retrieves the cached API response from a local file if it exists and is still valid.
     *
     * @param string|null $localCacheFilePath The path to the local cache file, or null if no cache file is used.
     * @return array|null The decoded response content as an associative array if the cached file is valid and readable, or null otherwise.
     */
    public function getLocalResponseCachedFile(?string $localCacheFilePath): ?array
    {
        if (!$localCacheFilePath || $this->developmentLocalCacheAgeInDays === 0 || !file_exists($localCacheFilePath)) {
            return null;
        }
        if (filemtime($localCacheFilePath) < (time() - 60 * 60 * 24 * $this->developmentLocalCacheAgeInDays)) {
            unlink($localCacheFilePath);
            return null;
        }
        $responseContent = file_get_contents($localCacheFilePath);
        if ($responseContent === false) {
            $this->logger->warning(sprintf('Failed to read local cache file: %s', $localCacheFilePath));
            // Proceed to API call
        } else {
            $localResponse = json_decode($responseContent, true);
            if (empty($localResponse) || array_key_exists('error', $localResponse)) {
                unlink($localCacheFilePath);
                return null;
            }
            return $localResponse;
        }
        return null;
    }

    /**
     * Generates the local cache file path for development context based on the given source type, source ID, and optional parameters.
     *
     * @param string $relativeUrl
     * @param ?array<string, string> $additionalValues Additional values for creating the cache key
     * @return string|null The generated local cache file path or null if local caching is not enabled or outside the development context.
     */
    public function getLocalCachePathForDevelopment(string $relativeUrl, ?array $additionalValues = null): ?string
    {
        if ($this->developmentLocalCacheAgeInDays > 0) {
            // Build a cache key from URL and params
            $cacheKey = $relativeUrl;
            if (!empty($additionalValues)) {
                foreach ($additionalValues as $key => $value) {
                    $cacheKey .= '-' . $key . '_' . $value;
                }
            }

            // Sanitize the cache key so it can be used safely as a file name
            // 1) Remove directory separators and query fragments by replacing any non safe char
            $safeKey = (string)preg_replace('/[^A-Za-z0-9._-]+/', '_', $cacheKey);
            // 2) Avoid leading dot (hidden files) and trim redundant underscores/dots/hyphens
            $safeKey = ltrim($safeKey, '.');
            $safeKey = trim($safeKey, '_-.');
            // 3) Ensure we still have something usable
            if ($safeKey === '') {
                $safeKey = 'cache';
            }
            // 4) Limit length and keep uniqueness using a hash suffix
            $hash = substr(sha1($cacheKey), 0, 12);
            // The max filename length on most filesystems is 255 bytes; leave room for suffix and extension
            $maxBaseLength = 255 - 1 /*dot*/ - 4 /*json*/ - 1 /*dash*/ - strlen($hash);
            if (strlen($safeKey) > $maxBaseLength) {
                $safeKey = substr($safeKey, 0, $maxBaseLength);
            }
            $fileName = $safeKey . '-' . $hash . '.json';
            return $this->getAbsoluteCachePath() . $fileName;
        }
        return null;
    }

    protected function getAbsoluteCachePath(): string
    {
        $localCachePath = Environment::getVarPath() . '/transient/' . $this->relativePath . '/';
        if (!is_dir($localCachePath)) {
            GeneralUtility::mkdir_deep($localCachePath);
        }
        return $localCachePath;
    }

    /**
     * Deletes old local cache files from the specified directory if they exceed the configured age limit.
     */
    final public function deleteOldLocalCacheFiles(): void
    {
        $localCachePath = $this->getAbsoluteCachePath();
        // Delete old cache files if it's a full dump call and the directory exists
        $maxAgeInSeconds = $this->developmentLocalCacheAgeInDays * 24 * 60 * 60;
        $now = time();
        try {
            $files = GeneralUtility::getFilesInDir($localCachePath, 'json', true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file)) > $maxAgeInSeconds) {
                        if (unlink($file)) {
                            $this->logger->debug(sprintf('Deleted old local cache file: %s', $file));
                        } else {
                            $this->logger->warning(sprintf('Failed to delete old local cache file: %s', $file));
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error while trying to delete old cache files in %s: %s', $localCachePath, $e->getMessage()));
        }
    }
}
