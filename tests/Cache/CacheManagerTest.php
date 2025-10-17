<?php
declare(strict_types=1);

use carry0987\I18n\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CacheManager::class)]
final class CacheManagerTest extends TestCase
{
    public function testSetLanguageDataAndGenerateCache(): void
    {
        $cache = new CacheManager();
        $data = ['foo' => 'bar'];

        $cache->setLanguageData($data);

        $tmpDir = sys_get_temp_dir() . '/i18n-test-cache';
        @mkdir($tmpDir, 0777, true);

        $cacheFile = $tmpDir . '/test_cache.php';
        $cache->generateCache($cacheFile);

        $this->assertFileExists($cacheFile);

        $result = include $cacheFile;
        $this->assertEquals($data, $result);

        @unlink($cacheFile);
        @rmdir($tmpDir);
    }

    public function testIsCacheValidWhenNoCacheFile(): void
    {
        $cache = new CacheManager();
        $result = $cache->isCacheValid('/tmp', '/tmp/does-not-exist.php');
        $this->assertFalse($result);
    }
}
