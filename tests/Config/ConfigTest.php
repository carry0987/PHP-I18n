<?php
declare(strict_types=1);

use carry0987\I18n\Config\Config;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Config::class)]
final class ConfigTest extends TestCase
{
    public function testSetAndGetOptions(): void
    {
        $config = new Config();
        $options = [
            'defaultLang' => 'zh_TW',
            'separator' => '_'
        ];
        $config->setOptions($options);
        $this->assertEquals('zh_TW', $config->getOptions('defaultLang'));
        $this->assertEquals('_', $config->getOptions('separator'));
    }

    public function testInvalidSeparatorThrows(): void
    {
        $config = new Config();
        $this->expectException(\carry0987\I18n\Exception\InvalidLanguageCodeException::class);
        $config->setOptions(['separator' => '--']);
    }
}
