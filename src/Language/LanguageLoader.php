<?php
namespace carry0987\I18n\Language;

use carry0987\I18n\I18n;
use carry0987\I18n\Config\Config;
use carry0987\I18n\Cache\CacheManager;
use carry0987\I18n\Exception\IOException;

class LanguageLoader
{
    private ?string $langFilePath;
    private ?string $cachePath;
    private array $allowedFiles;
    private array $languageData = [];
    private static string $currentLang;

    public function __construct(Config $config)
    {
        $this->langFilePath = $config->getOptions('langFilePath');
        $this->cachePath = $config->getOptions('cachePath');
        $this->allowedFiles = $config->getOptions('allowedFiles') ?? [];
    }

    public function setCurrentLang(string $lang): self
    {
        self::$currentLang = $lang;

        return $this;
    }

    public function loadLanguageFile(string $filePath, string $fileName): void
    {
        if (file_exists($filePath)) {
            $jsonData = file_get_contents($filePath);
            if ($jsonData === false) {
                throw new IOException('Unable to read the language file {'.$filePath.'}.');
            }
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new IOException('Error parsing JSON file {'.$filePath.'} '.json_last_error_msg());
            }
            // Use the file name as the key to add to the data structure
            $this->languageData[$fileName] = $data;
        } else {
            throw new IOException('Language file does not exist: {'.$filePath.'}');
        }
    }

    public function loadLanguageData(): void
    {
        $directory = $this->langFilePath.I18n::DIR_SEP.self::$currentLang;
        if (!is_dir($directory)) {
            throw new IOException('Language folder does not exist: {'.$directory.'}');
        }

        if ($this->cachePath) {
            $cacheManager = new CacheManager();
            $cacheFile = $this->cachePath.I18n::DIR_SEP.self::$currentLang.'.php';
            if ($cacheManager->isCacheValid($directory, $cacheFile)) {
                $this->languageData = include $cacheFile;
            } else {
                $this->loadAllLanguageFiles();
                $cacheManager->setLanguageData($this->languageData);
                $cacheManager->generateCache($cacheFile);
            }
        } else {
            $this->loadAllLanguageFiles();
        }
    }

    public function getLanguageData(): array
    {
        return $this->languageData;
    }

    public function getValue(string $lang, string $key): string
    {
        $keys = explode('.', $key);
        if (count($keys) < 2) {
            // If the key does not specify the namespace of the file name, throw an error
            throw new IOException('Language key must include a namespace: '.$key);
        }

        $fileKey = array_shift($keys); // Fetch the file name as namespace
        if (!empty($this->allowedFiles) && !in_array($fileKey, $this->allowedFiles)) {
            // If the namespace is not in the file list, throw an error directly
            throw new IOException('Namespace {'.$fileKey.'} is not in the specified file list.');
        }
        $translationKey = implode('.', $keys); // Combine the remaining key path

        // Try to load the language data for the corresponding file first
        $filePath = $this->langFilePath.I18n::DIR_SEP.$lang.I18n::DIR_SEP.$fileKey.'.json';
        if (isset($this->languageData[$fileKey], $this->languageData[$fileKey][$translationKey])) {
            // If the language data has already been loaded, get the translation directly from the data structure
            return $this->languageData[$fileKey][$translationKey];
        } elseif (file_exists($filePath)) {
            // If the language file has not been loaded yet, load it now and extract the translation
            $this->loadLanguageFile($filePath, $fileKey);
            return $this->languageData[$fileKey][$translationKey] ?? null;
        }

        // If the file does not exist or the key does not exist, throw an error
        throw new IOException('Unable to find the specified language key: '.$key);
    }

    public function getAllValues(array $fileList): array
    {
        // Fetch all language files if both the argument and $this->allowedFiles are empty
        if (empty($fileList)) {
            if (empty($this->allowedFiles)) {
                // Load all files in the language directory
                $directory = $this->langFilePath.I18n::DIR_SEP.self::$currentLang;
                $files = glob($directory.I18n::DIR_SEP.'*.json');
                foreach ($files as $file) {
                    $fileName = basename($file, '.json');
                    $fileList[] = $fileName;
                }
            } else {
                // Use $this->allowedFiles if it's not empty
                $fileList = $this->allowedFiles;
            }
        } else {
            // Intersect with $this->allowedFiles if it's not empty
            $fileList = (empty($this->allowedFiles)) ? $fileList : array_intersect($fileList, $this->allowedFiles);
        }

        $allData = [];
        foreach ($fileList as $fileName) {
            $filePath = $this->langFilePath.I18n::DIR_SEP.self::$currentLang.I18n::DIR_SEP.$fileName.'.json';
            if (isset($this->languageData[$fileName])) {
                // If the language data has already been loaded, use it directly
                $allData[$fileName] = $this->languageData[$fileName];
            } elseif (file_exists($filePath)) {
                // Load the language file data and add it to the allData array
                $this->loadLanguageFile($filePath, $fileName);
                $allData[$fileName] = $this->languageData[$fileName];
            } else {
                // If the file does not exist, add empty data for the file
                $allData[$fileName] = [];
            }
        }

        return $allData;
    }

    private function loadAllLanguageFiles(): void
    {
        $directory = $this->langFilePath.I18n::DIR_SEP.self::$currentLang;
        $files = glob($directory.I18n::DIR_SEP.'*.json');
        foreach ($files as $file) {
            $fileName = basename($file, '.json');
            if (substr_count($fileName, '.') > 0) {
                throw new IOException('Invalid file name. The file names should not contain more than one dot: {'.$fileName.'}');
            }
            if (!empty($this->allowedFiles) && !in_array($fileName, $this->allowedFiles)) {
                throw new IOException('Language file {'.$fileName.'} not in the specified file list.');
            }
            $this->loadLanguageFile($file, $fileName);
        }
    }
}
