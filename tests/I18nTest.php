<?php
declare(strict_types=1);

use carry0987\I18n\I18n;
use carry0987\I18n\Cache\CacheManager;
use carry0987\I18n\Config\Config;
use carry0987\I18n\Language\LanguageCodeValidator;
use carry0987\I18n\Language\LanguageLoader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(I18n::class)]
#[UsesClass(CacheManager::class)]
#[UsesClass(Config::class)]
#[UsesClass(LanguageCodeValidator::class)]
#[UsesClass(LanguageLoader::class)]
final class I18nTest extends TestCase
{
    public function testCanFetchTranslation(): void
    {
        // Arrange
        $options = [
            'langFilePath' => __DIR__ . '/../lang',
            'cachePath' => __DIR__ . '/../.cache',
            'defaultLang' => 'en_US',
            'autoSearch' => true
        ];
        $i18n = new I18n($options);
        $i18n->initialize('zh_TW');

        // Act
        $value = $i18n->fetch('greeting.hello');
        $this->assertEquals('你好', $value); // Assume greeting.json has { "hello": "你好" }
        $value = $i18n->fetch('greeting.world');
        $this->assertEquals('World', $value); // Assume greeting.json has { "world": "World" }
        $value = $i18n->fetch('general.today', ['2024', '06', '15']);
        $this->assertEquals('今天是 2024 年 06 月 15 日', $value); // Assume general.json has { "today": "今天是 %s 年 %s 月 %s 日" }
    }

    public function testFetchWithoutAutoSearch(): void
    {
        $options = [
            'langFilePath' => __DIR__ . '/../lang',
            'cachePath' => __DIR__ . '/../.cache',
            'defaultLang' => 'en_US',
            'autoSearch' => false
        ];
        $i18n = new I18n($options);
        $i18n->initialize('zh_TW');

        $value = $i18n->fetch('greeting.hello');
        $this->assertEquals('你好', $value);
        $this->expectException(\carry0987\I18n\Exception\IOException::class);
        $i18n->fetch('greeting.world');
    }

    public function testFetchMissingKey(): void
    {
        $options = [
            'langFilePath' => __DIR__ . '/../lang',
            'cachePath' => __DIR__ . '/../.cache',
            'defaultLang' => 'en_US',
            'autoSearch' => true
        ];
        $i18n = new I18n($options);
        $i18n->initialize('zh_TW');

        $value = $i18n->fetch('general.somethingThatDoesNotExist');
        $this->assertNull($value);
    }

    public function testFetchCurrentLang(): void
    {
        $options = [
            'langFilePath' => __DIR__ . '/../lang',
            'defaultLang' => 'en_US',
            'autoSearch' => true
        ];
        $i18n = new I18n($options);
        $i18n->initialize('zh_TW');
        $this->assertEquals('zh_TW', $i18n->fetchCurrentLang());
    }

    public function testFetchWithoutInitThrows(): void
    {
        $options = [
            'langFilePath' => __DIR__ . '/../lang',
            'defaultLang' => 'en_US'
        ];
        $i18n = new I18n($options);

        $this->expectException(\carry0987\I18n\Exception\InitializationException::class);
        $i18n->fetch('greeting.hello');
    }
}
