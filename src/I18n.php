<?php
declare(strict_types=1);

namespace carry0987\I18n;

use carry0987\I18n\Language\LanguageLoader;
use carry0987\I18n\Language\LanguageCodeValidator;
use carry0987\I18n\Config\Config;
use carry0987\I18n\Cookie\CookieService;
use carry0987\I18n\Exception\InitializationException;
use carry0987\I18n\Exception\IOException;
use carry0987\Utils\Utils;

class I18n
{
    // Class properties
    private LanguageLoader $languageLoader;
    private LanguageCodeValidator $languageCodeValidator;
    private Config $config;
    private CookieService $cookieService;

    // Instance properties
    private string $currentLang;
    private bool $initialized = false;

    // Options
    private static array $option;

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

    public function initialize(?string $language = null): void
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

    public function setLangAlias(array $alias): self
    {
        $this->languageCodeValidator->setLangAlias($alias);

        return $this;
    }

    public function getLangAlias(): array
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using getLangAlias().');
        }

        return $this->fetchLangList();
    }

    public function fetch(string $key, ?array $param = null): ?string
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetch().');
        }

        try {
            $value = $this->languageLoader->getValue($this->currentLang, $key);
        } catch (IOException $e) {
            if (self::$option['autoSearch'] && $this->currentLang !== self::$option['defaultLang']) {
                try {
                    $value = $this->languageLoader->getValue(self::$option['defaultLang'], $key);
                } catch (IOException $eDefault) {
                    // Not found in defaultLang
                    $value = null;
                }
            } else {
                // Rethrow for strict mode
                throw $e;
            }
        }

        if ($param !== null && $value !== null) {
            $value = self::langParam($value, $param);
        }

        return $value;
    }

    public function fetchList(array $fileList = []): array
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetchList().');
        }

        return $this->languageLoader->getAllValues($fileList);
    }

    public function fetchLangList(): array
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetchLangList().');
        }

        return $this->languageCodeValidator->getLanguageList();
    }

    public function fetchCurrentLang(): string
    {
        if (!$this->initialized) {
            throw new InitializationException('I18n class must be initialized before using fetchCurrentLang().');
        }

        return $this->currentLang;
    }

    public static function langParam(string $value, array $param): string
    {
        $replacements = [];

        // Construct replacement array
        foreach ($param as $index => $p) {
            $replacements['{' . $index . '}'] = $p; // Placeholder as key
        }

        return strtr($value, $replacements);
    }

    private function validateLanguageFolder(string $folder): string
    {
        $folderPath = self::$option['langFilePath'].Utils::DIR_SEP.$folder;
        if (!is_dir($folderPath)) {
            throw new IOException('Language folder does not exist: {'.$folder.'}');
        }

        return $folder;
    }
}
