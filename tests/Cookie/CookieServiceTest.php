<?php
declare(strict_types=1);

use carry0987\I18n\Cookie\CookieService;
use carry0987\I18n\Config\Config;
use carry0987\I18n\Language\LanguageCodeValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(CookieService::class)]
#[UsesClass(Config::class)]
#[UsesClass(LanguageCodeValidator::class)]
final class CookieServiceTest extends TestCase
{
    public function testGetLanguageFromCookieDefault(): void
    {
        $_COOKIE = [];
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        $config = new Config();
        $config->setOptions([
            'langFilePath' => __DIR__ . '/../../lang',
            'defaultLang' => 'en_US'
        ]);
        $service = new CookieService($config);

        $result = $service->getLanguageFromCookie();
        $this->assertEquals('en_US', $result);
    }
}
