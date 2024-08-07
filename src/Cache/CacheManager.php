<?php
declare(strict_types=1);

namespace carry0987\I18n\Cache;

use carry0987\I18n\Exception\IOException;
use carry0987\Utils\Utils;

class CacheManager
{
    private array $languageData;

    public function __construct()
    {
    }

    public function setLanguageData(array $data): self
    {
        $this->languageData = $data;

        return $this;
    }

    public function isCacheValid(string $directory, string $cacheFile): bool
    {
        if (!file_exists($cacheFile)) {
            return false;
        }

        $cachedTime = filemtime($cacheFile);
        $files = glob($directory.Utils::DIR_SEP.'*.json');
        foreach ($files as $file) {
            if (filemtime($file) > $cachedTime) {
                return false;
            }
        }

        return true;
    }

    public function generateCache(string $cacheFileName): void
    {
        if (!Utils::makePath(dirname($cacheFileName))) {
            throw new IOException('Unable to create cache directory for file {'.$cacheFileName.'}.');
        }

        $cacheData = '<?php return '.var_export($this->languageData, true).';';
        if (file_put_contents($cacheFileName, $cacheData) === false) {
            throw new IOException('Unable to write cache file {'.$cacheFileName.'}.');
        }
    }
}
