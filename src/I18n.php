<?php
namespace carry0987\I18n;

use carry0987\I18n\Exception\InitializationException;
use carry0987\I18n\Exception\InvalidLanguageCodeException;
use carry0987\I18n\Exception\IOException;

class I18n
{
    private $cachePath;
    private $langFilePath;
    private $fileList = [];
    private $defaultLang = 'en_US';
    private $currentLang;
    private $languageData = [];
    private $initialized = false;
    private $countryCodeUpperCase;
    private $separator;
    private $autoSearch;
    private $defaultOptions = [
        'defaultLang' => 'en_US',
        'separator' => '_',
        'autoSearch' => false,
        'countryCodeUpperCase' => true
    ];

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

    public function setFileList(array $files)
    {
        $this->fileList = $files;
    }

    private function loadLanguageFile($filePath, $fileName)
    {
        if (!empty($this->fileList) && !in_array($fileName, $this->fileList)) {
            throw new IOException('Language file not in the specified file list: {'.$fileName.'}');
        }
        if (file_exists($filePath)) {
            $jsonData = file_get_contents($filePath);
            if ($jsonData === false) {
                throw new IOException('Unable to read the language file {'.$filePath.'}.');
            }
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new IOException('Error parsing JSON file {'.$filePath.'} '.json_last_error_msg());
            }
            $this->languageData = array_merge($this->languageData, $data);
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
            if (!empty($this->fileList) && !in_array($fileName, $this->fileList)) {
                continue;
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

    private function getValue($lang, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) > 1) {
            $fileName = array_shift($parts);
            $key = implode('.', $parts);
            $filePath = $this->langFilePath.self::DIR_SEP.$lang.self::DIR_SEP.$fileName.'.json';
            $this->loadLanguageFile($filePath, $fileName);
        } else {
            $this->loadLanguageFolder($lang);
        }

        return $this->languageData[$key] ?? null;
    }

    private function loadLanguageFolder($lang)
    {
        $directory = $this->langFilePath.self::DIR_SEP.$lang;
        if (!is_dir($directory)) {
            throw new IOException('Language folder does not exist: {'.$directory.'}');
        }
        if ($handle = opendir($directory)) {
            while (false !== ($fileName = readdir($handle))) {
                if ($fileName !== "." && $fileName !== ".." && !empty($this->fileList) && !in_array($fileName, $this->fileList)) {
                    $this->loadLanguageFile($directory.self::DIR_SEP.$fileName, basename($fileName, '.json'));
                }
            }
            closedir($handle);
        }
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

    private function formatLanguageCode($code)
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
        $dirs = explode(self::DIR_SEP, dirname(self::trimPath($path)));
        if (empty($dirs[0])) {
            $dirs[0] = self::DIR_SEP;
        }
        // Check if $dirs[0] is a directory and is writable
        if (!is_dir($dirs[0]) || !is_writable($dirs[0])) {
            throw new IOException('Directory {'.$dirs[0].'} is not writable.');
        }

        $tmp = '';
        foreach ($dirs as $dir) {
            $tmp = $tmp.$dir.self::DIR_SEP;
            if (!file_exists($tmp) && !mkdir($tmp, 0755, true)) {
                return $tmp;
            }
        }

        return true;
    }
}
