<?php
namespace carry0987\I18n\Cookie;

use carry0987\I18n\Config\Config;
use carry0987\I18n\Language\LanguageCodeValidator;

class CookieService
{
    private $defaultLang;
    private $languageCodeValidator;
    private static $cookieConfig;

    public function __construct(Config $config)
    {
        $this->defaultLang = $config->getOptions('defaultLang');
        self::$cookieConfig = $config->getOptions('cookie');
        $this->languageCodeValidator = new LanguageCodeValidator($config);
    }

    public function getLanguageFromCookie()
    {
        $language = $this->defaultLang;

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !isset($_COOKIE[self::$cookieConfig['name']])) {
            $browserLang = $this->languageCodeValidator->formatAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if ($this->languageCodeValidator->isLanguageSupported($browserLang)) {
                $language = $browserLang;
            }
            $this->setLanguageCookie($language);
        } elseif (isset($_COOKIE[self::$cookieConfig['name']]) && $this->languageCodeValidator->isLanguageSupported($_COOKIE[self::$cookieConfig['name']])) {
            $language = $_COOKIE[self::$cookieConfig['name']];
        }

        return $language;
    }

    private function setLanguageCookie(string $lang)
    {
        $config = self::$cookieConfig;

        return setcookie($config['name'], $lang, $config['expire'], $config['path'], $config['domain'], $config['secure'], $config['httponly']);
    }
}
