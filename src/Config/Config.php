<?php
namespace carry0987\I18n\Config;

use carry0987\I18n\I18n;
use carry0987\I18n\Exception\InvalidLanguageCodeException;

class Config
{
    private static $defaultOptions = array(
        'useAutoDetect' => false,
        'defaultLang' => 'en_US',
        'langFilePath' => null,
        'cachePath' => null,
        'separator' => '_',
        'autoSearch' => false,
        'countryCodeUpperCase' => true,
        'cookie' => []
    );
    private static $cookieConfig = array(
        'name' => 'lang',
        'expire' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true
    );
    private static $config = array();

    public static function setOptions(array $options): void
    {
        $options = array_merge(self::$defaultOptions, $options);
        self::$config['useAutoDetect'] = $options['useAutoDetect'];
        self::$config['defaultLang'] = $options['defaultLang'];
        self::$config['langFilePath'] = isset($options['langFilePath']) ? I18n::trimPath($options['langFilePath']) : null;
        self::$config['cachePath'] = isset($options['cachePath']) ? I18n::trimPath($options['cachePath']) : null;
        self::$config['separator'] = $options['separator'];
        self::$config['autoSearch'] = $options['autoSearch'];
        self::$config['countryCodeUpperCase'] = $options['countryCodeUpperCase'];

        // Set cookie options
        self::$config['cookie'] = self::$cookieConfig;
        if (!empty($options['cookie'])) {
            self::$config['cookie'] = self::setCookieConfig($options['cookie']);
        }
        // Make sure separator is a single character
        if (strlen(self::$config['separator']) != 1) {
            throw new InvalidLanguageCodeException('Invalid separator. It must be a single character.');
        }
    }

    public static function getOptions(string $key = null, mixed $default = null): mixed
    {
        if ($key) {
            return self::$config[$key] ?? $default;
        }

        return self::$config;
    }

    private static function setCookieConfig(array $config): array
    {
        self::$cookieConfig = array_merge(self::$cookieConfig, $config);

        return self::$cookieConfig;
    }
}
