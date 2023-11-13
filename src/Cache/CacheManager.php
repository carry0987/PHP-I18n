<?php
namespace carry0987\I18n\Cache;

use carry0987\I18n\I18n;
use carry0987\I18n\Exception\IOException;

class CacheManager
{
    private $languageData;

    public function __construct()
    {
    }

    public function setLanguageData(array $data)
    {
        $this->languageData = $data;
    }

    public function isCacheValid(string $directory, string $cacheFile)
    {
        if (!file_exists($cacheFile)) {
            return false;
        }

        $cachedTime = filemtime($cacheFile);
        $files = glob($directory.I18n::DIR_SEP.'*.json');
        foreach ($files as $file) {
            if (filemtime($file) > $cachedTime) {
                return false;
            }
        }

        return true;
    }

    public function generateCache(string $cacheFileName)
    {
        if (!I18n::makePath(dirname($cacheFileName))) {
            throw new IOException('Unable to create cache directory for file {'.$cacheFileName.'}.');
        }

        $cacheData = '<?php return '.var_export($this->languageData, true).';';
        if (file_put_contents($cacheFileName, $cacheData) === false) {
            throw new IOException('Unable to write cache file {'.$cacheFileName.'}.');
        }
    }
}
