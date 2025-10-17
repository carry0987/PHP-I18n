<?php
declare(strict_types=1);

use carry0987\I18n\Config\Config;
use carry0987\I18n\Language\LanguageCodeValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(LanguageCodeValidator::class)]
#[UsesClass(Config::class)]
final class LanguageCodeValidatorTest extends TestCase
{
    public function testValidateLanguageCode(): void
    {
        $config = new Config();
        $config->setOptions([
            'langFilePath' => __DIR__ . '/../lang',
            'defaultLang' => 'en_US'
        ]);
        $validator = new LanguageCodeValidator($config);
        $result = $validator->validateLanguageCode('en_US');
        $this->assertEquals('en_US', $result);
    }

    public function testValidateLanguageCodeThrows(): void
    {
        $config = new Config();
        $config->setOptions([
            'langFilePath' => __DIR__ . '/../lang'
        ]);
        $validator = new LanguageCodeValidator($config);
        $this->expectException(\carry0987\I18n\Exception\InvalidLanguageCodeException::class);
        $validator->validateLanguageCode('invalid_code');
    }
}
