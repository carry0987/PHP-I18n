<?php
declare(strict_types=1);

use carry0987\I18n\Config\Config;
use carry0987\I18n\Language\LanguageLoader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(LanguageLoader::class)]
#[UsesClass(Config::class)]
final class LanguageLoaderTest extends TestCase
{
    public function testSetCurrentLangAndGetValue(): void
    {
        $config = new Config();
        $config->setOptions([
            'langFilePath' => __DIR__ . '/../../lang'
        ]);
        $loader = new LanguageLoader($config);
        $loader->setCurrentLang('en_US');
        $loader->loadLanguageData();

        $value = $loader->getValue('en_US', 'greeting.hello');
        $this->assertEquals('Hello', $value);
    }
}
