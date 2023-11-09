<?php
namespace carry0987\I18n;

use carry0987\I18n\Exception\InitializationException;
use carry0987\I18n\Exception\InvalidLanguageCodeException;
use carry0987\I18n\Exception\IOException;

class I18n
{
    private $cachePath;
    private $langFilePath;
    private $allowedFiles = [];
    private $defaultLang = 'en_US';
    private $currentLang;
    private $languageData = [];
    private $initialized = false;
    private $countryCodeUpperCase;
    private $separator;
    private $autoSearch;
    private $defaultOptions = [
        'defaultLang' => 'en_US',
        'langFilePath' => null,
        'cachePath' => null,
        'separator' => '_',
        'autoSearch' => false,
        'countryCodeUpperCase' => true
    ];
    private $langAlias = [];

    const DIR_SEP = DIRECTORY_SEPARATOR;

    public function __construct(array $option = [])
    {
        return $this->setOptions($option);
    }

    private function setOptions(array $options)
    {
        $options = array_merge($this->defaultOptions, $options);
        $this->defaultLang = $options['defaultLang'];
        $this->langFilePath = isset($options['langFilePath']) ? self::trimPath($options['langFilePath']) : null;
        $this->cachePath = isset($options['cachePath']) ? self::trimPath($options['cachePath']) : null;
        $this->separator = $options['separator'];
        $this->autoSearch = $options['autoSearch'];
        $this->countryCodeUpperCase = $options['countryCodeUpperCase'];
        // Make sure separator is a single character
        if (strlen($this->separator) != 1) {
            throw new InvalidLanguageCodeException('Invalid separator. It must be a single character.');
        }
    }

    public function setLangAlias(array $alias)
    {
        $this->langAlias = $alias;
    }

    public function initialize(string $language = null)
    {
        if ($this->initialized) {
            throw new InitializationException('The I18n class has already been initialized');
        }

        $language = $language ?: $this->defaultLang;
        $this->currentLang = $this->validateLanguageCode($language);
        $this->validateLanguageFolder($this->currentLang);
        $this->loadLanguageData();
        $this->initialized = true;
    }

    public function setAllowedFiles(array $files)
    {
        $this->allowedFiles = $files;
    }

    private function loadLanguageFile(string $filePath, string $fileName)
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

    private function loadAllLanguageFiles()
    {
        $directory = $this->langFilePath.self::DIR_SEP.$this->currentLang;
        $files = glob($directory.self::DIR_SEP.'*.json');
        foreach ($files as $file) {
            $fileName = basename($file, '.json');
            if (!empty($this->allowedFiles) && !in_array($fileName, $this->allowedFiles)) {
                throw new IOException('Language file {'.$fileName.'} not in the specified file list.');
            }
            $this->loadLanguageFile($file, $fileName);
        }
    }

    private function loadLanguageData()
    {
        $directory = $this->langFilePath.self::DIR_SEP.$this->currentLang;
        if (!is_dir($directory)) {
            throw new IOException('Language folder does not exist: {'.$directory.'}');
        }

        if ($this->cachePath) {
            $cacheFile = $this->cachePath.self::DIR_SEP.$this->currentLang.'.php';
            if ($this->isCacheValid($directory, $cacheFile)) {
                $this->languageData = include $cacheFile;
            } else {
                $this->loadAllLanguageFiles();
                $this->generateCache($cacheFile);
            }
        } else {
            $this->loadAllLanguageFiles();
        }
    }

    private function isCacheValid(string $directory, string $cacheFile)
    {
        if (!file_exists($cacheFile)) {
            return false;
        }

        $cachedTime = filemtime($cacheFile);
        $files = glob($directory.self::DIR_SEP.'*.json');
        foreach ($files as $file) {
            if (filemtime($file) > $cachedTime) {
                return false;
            }
        }

        return true;
    }

    private function generateCache(string $cacheFileName)
    {
        if (!self::makePath(dirname($cacheFileName))) {
            throw new IOException('Unable to create cache directory for file {'.$cacheFileName.'}.');
        }

        $cacheData = '<?php return '.var_export($this->languageData, true).';';
        if (file_put_contents($cacheFileName, $cacheData) === false) {
            throw new IOException('Unable to write cache file {'.$cacheFileName.'}.');
        }
    }

    public function fetch(string $key)
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetch().');
        }
        $value = $this->getValue($this->currentLang, $key);

        if ($value === null && $this->autoSearch && $this->currentLang !== $this->defaultLang) {
            $value = $this->getValue($this->defaultLang, $key);
        }

        return $value;
    }

    public function fetchList(array $fileList = [])
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetchAll().');
        }

        // Fetch all language files if both the argument and $this->allowedFiles are empty
        if (empty($fileList)) {
            if (empty($this->allowedFiles)) {
                // Load all files in the language directory
                $directory = $this->langFilePath.self::DIR_SEP.$this->currentLang;
                $files = glob($directory.self::DIR_SEP.'*.json');
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
            $filePath = $this->langFilePath.self::DIR_SEP.$this->currentLang.self::DIR_SEP.$fileName.'.json';
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

    public function fetchLangList()
    {
        $langDir = $this->langFilePath;
        if (!is_dir($langDir)) {
            throw new IOException('Language directory does not exist: {'.$langDir.'}');
        }

        $directories = glob($langDir.self::DIR_SEP.'*', GLOB_ONLYDIR);
        $langList = [];
        foreach ($directories as $dir) {
            $langCode = basename($dir);
            // Use alias if it exists; otherwise use language code
            $alias = $this->langAlias[$langCode] ?? $langCode;
            $langList[$langCode] = $alias;
        }

        return $langList;
    }

    private function getValue(string $lang, string $key)
    {
        $keys = explode('.', $key);
        if (count($keys) < 2) {
            // If the key does not specify the namespace of the file name, throw an error
            throw new Exception\IOException('Language key must include a namespace: '.$key);
        }

        $fileKey = array_shift($keys); // Fetch the file name as namespace
        if (!empty($this->allowedFiles) && !in_array($fileKey, $this->allowedFiles)) {
            // If the namespace is not in the file list, throw an error directly
            throw new Exception\IOException('Namespace {'.$fileKey.'} is not in the specified file list.');
        }
        $translationKey = implode('.', $keys); // Combine the remaining key path

        // Try to load the language data for the corresponding file first
        $filePath = $this->langFilePath.self::DIR_SEP.$lang.self::DIR_SEP.$fileKey.'.json';
        if (isset($this->languageData[$fileKey], $this->languageData[$fileKey][$translationKey])) {
            // If the language data has already been loaded, get the translation directly from the data structure
            return $this->languageData[$fileKey][$translationKey];
        } elseif (file_exists($filePath)) {
            // If the language file has not been loaded yet, load it now and extract the translation
            $this->loadLanguageFile($filePath, $fileKey);
            return $this->languageData[$fileKey][$translationKey] ?? null;
        }

        // If the file does not exist or the key does not exist, throw an error
        throw new Exception\IOException('Unable to find the specified language key: '.$key);
    }

    private function validateLanguageFolder(string $folder)
    {
        $folderPath = $this->langFilePath.self::DIR_SEP.$folder;
        if (!is_dir($folderPath)) {
            throw new IOException('Language folder does not exist: {'.$folder.'}');
        }

        return $folder;
    }

    private function validateLanguageCode(string $code)
    {
        $pattern = $this->getLanguagePattern();
        if (!preg_match($pattern, $code)) {
            throw new InvalidLanguageCodeException('Invalid language code: {'.$code.'}');
        }

        return $this->formatLanguageCode($code);
    }

    private function getLanguagePattern()
    {
        $separatorQuoted = preg_quote($this->separator, '/');
        $languagePart = '[a-z]{2}';
        $countryPart = $this->countryCodeUpperCase ? '[A-Z]{2}' : '[a-z]{2}';

        return '/^'.$languagePart.$separatorQuoted.$countryPart.'$/';
    }

    private function formatLanguageCode(string $code)
    {
        $parts = explode($this->separator, $code);
        $parts[0] = strtolower($parts[0]);
        $parts[1] = $this->countryCodeUpperCase ? strtoupper($parts[1]) : strtolower($parts[1]);

        return implode($this->separator, $parts);
    }

    private static function trimPath(string $path)
    {
        return str_replace(array('/', '\\', '//', '\\\\'), self::DIR_SEP, $path);
    }

    private static function makePath(string $path)
    {
        $path = self::trimPath($path); // Ensure path format is consistent.
        $isAbsolute = (strpos($path, self::DIR_SEP) === 0); // Determine if it's an absolute path.
        // Handle both absolute and relative paths.
        $currentPath = $isAbsolute ? self::DIR_SEP : '';
        // Split the path into individual parts.
        $parts = array_filter(explode(self::DIR_SEP, $path), 'strlen');
        // Loop through parts to create directories.
        foreach ($parts as $part) {
            if ($part === '..') {
                // If it's a parent directory indicator, move to the parent of the current path.
                $currentPath = dirname($currentPath);
            } else {
                // Otherwise, it's a directory and we should attempt to create it.
                $currentPath .= $part . self::DIR_SEP;
                if (!is_dir($currentPath) && !mkdir($currentPath, 0755, true)) {
                    throw new IOException('Unable to create directory ' . $currentPath);
                }
            }
        }

        return true; // Return true if all directories have been successfully created or already exist.
    }
}
