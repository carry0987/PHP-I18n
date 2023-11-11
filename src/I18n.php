<?php
namespace carry0987\I18n;

use carry0987\I18n\Language\LanguageLoader;
use carry0987\I18n\Language\LanguageCodeValidator;
use carry0987\I18n\Config\Config;
use carry0987\I18n\Cookie\CookieService;
use carry0987\I18n\Exception\InitializationException;
use carry0987\I18n\Exception\IOException;

class I18n
{
    private $languageLoader;
    private $languageCodeValidator;
    private $config;
    private $cookieService;

    private $currentLang;
    private $initialized = false;

    private static $option;

    const DIR_SEP = DIRECTORY_SEPARATOR;

    public function __construct(array $options = [])
    {
        $this->config = new Config();
        $this->config->setOptions($options);
        self::$option = $this->config->getOptions();
        $this->languageLoader = new LanguageLoader($this->config);
        $this->languageCodeValidator = new LanguageCodeValidator($this->config);

        // Set language automatically based on browser settings
        if (self::$option['useAutoDetect']) {
            $this->cookieService = new CookieService($this->config);
            $language = $this->cookieService->getLanguageFromCookie();
            $this->initialize($language);
        }

        return $this;
    }

    public function initialize(string $language = null)
    {
        if ($this->initialized) {
            throw new InitializationException('The I18n class has already been initialized');
        }

        $language = $language ?: self::$option['defaultLang'];
        $this->currentLang = $this->languageCodeValidator->validateLanguageCode($language);
        $this->validateLanguageFolder($this->currentLang);
        $this->languageLoader->setCurrentLang($this->currentLang);
        $this->languageLoader->loadLanguageData();
        $this->initialized = true;
    }

    public function setLangAlias(array $alias)
    {
        $this->languageCodeValidator->setLangAlias($alias);
    }

    public function fetch(string $key)
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetch().');
        }

        $value = $this->languageLoader->getValue($this->currentLang, $key);

        if ($value === null && self::$option['autoSearch'] && $this->currentLang !== self::$option['defaultLang']) {
            $value = $this->languageLoader->getValue(self::$option['defaultLang'], $key);
        }

        return $value;
    }

    public function fetchList(array $fileList = [])
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetchList().');
        }

        return $this->languageLoader->getAllValues($fileList);
    }

    public function fetchLangList()
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetchLangList().');
        }

        return $this->languageCodeValidator->getLanguageList();
    }

    public function fetchCurrentLang()
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetchCurrentLang().');
        }

        return $this->currentLang;
    }

    private function validateLanguageFolder(string $folder)
    {
        $folderPath = self::$option['langFilePath'].self::DIR_SEP.$folder;
        if (!is_dir($folderPath)) {
            throw new IOException('Language folder does not exist: {'.$folder.'}');
        }

        return $folder;
    }

    public static function trimPath(string $path)
    {
        return str_replace(array('/', '\\', '//', '\\\\'), self::DIR_SEP, $path);
    }

    public static function makePath(string $path)
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
                $currentPath .= $part.self::DIR_SEP;
                if (!is_dir($currentPath) && !mkdir($currentPath, 0755, true)) {
                    throw new IOException('Unable to create directory '.$currentPath);
                }
            }
        }

        return true; // Return true if all directories have been successfully created or already exist.
    }
}
