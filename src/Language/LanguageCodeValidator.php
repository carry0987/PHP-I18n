<?php
namespace carry0987\I18n\Language;

use carry0987\I18n\I18n;
use carry0987\I18n\Config\Config;
use carry0987\I18n\Exception\IOException;
use carry0987\I18n\Exception\InvalidLanguageCodeException;

class LanguageCodeValidator
{
    private string $separator;
    private bool $countryCodeUpperCase;
    private string $defaultLang;
    private string $langFilePath;
    private array $langAlias;

    public function __construct(Config $config)
    {
        $this->separator = $config->getOptions('separator');
        $this->countryCodeUpperCase = $config->getOptions('countryCodeUpperCase');
        $this->defaultLang = $config->getOptions('defaultLang');
        $this->langFilePath = $config->getOptions('langFilePath');
    }

    public function setLangAlias(array $alias): self
    {
        $this->langAlias = $alias;

        return $this;
    }

    public function validateLanguageCode(string $code): string
    {
        $pattern = $this->getLanguagePattern();
        if (!preg_match($pattern, $code)) {
            throw new InvalidLanguageCodeException('Invalid language code: {'.$code.'}');
        }

        return $this->formatLanguageCode($code);
    }

    public function formatAcceptLanguage(string $acceptLanguage): string
    {
        if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $acceptLanguage)) {
            return $acceptLanguage;
        }
        $langs = explode(',', $acceptLanguage);
        $primaryLang = explode(';', $langs[0])[0];
        $parts = explode('-', $primaryLang);
        if (count($parts) === 2) {
            return strtolower($parts[0]) . '_' . strtoupper($parts[1]);
        }

        return $this->defaultLang;
    }

    public function isLanguageSupported(string $lang): bool
    {
        return preg_match($this->getLanguagePattern(), $lang) && in_array($lang, $this->getLanguageList());
    }

    public function getLanguageList(): array
    {
        $langDir = $this->langFilePath;
        if (!is_dir($langDir)) {
            throw new IOException('Language directory does not exist: {'.$langDir.'}');
        }

        $directories = glob($langDir.I18n::DIR_SEP.'*', GLOB_ONLYDIR);
        $langList = [];
        foreach ($directories as $dir) {
            $langCode = basename($dir);
            // Use alias if it exists; otherwise use language code
            $alias = $this->langAlias[$langCode] ?? $langCode;
            $langList[$langCode] = $alias;
        }

        return $langList;
    }

    private function getLanguagePattern(): string
    {
        $separatorQuoted = preg_quote($this->separator, '/');
        $languagePart = '[a-z]{2}';
        $countryPart = $this->countryCodeUpperCase ? '[A-Z]{2}' : '[a-z]{2}';

        return '/^'.$languagePart.$separatorQuoted.$countryPart.'$/';
    }

    private function formatLanguageCode(string $code): string
    {
        $parts = explode($this->separator, $code);
        $parts[0] = strtolower($parts[0]);
        $parts[1] = $this->countryCodeUpperCase ? strtoupper($parts[1]) : strtolower($parts[1]);

        return implode($this->separator, $parts);
    }
}
