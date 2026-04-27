<?php
/**
 * ComposerDependencyManager Test
 *
 * Tests for the ComposerDependencyManager class in FA_PM module
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\Common\ComposerDependencyManager;

class ComposerDependencyManagerTest extends TestCase
{
    private string $testModuleDir;
    private string $composerJsonPath;
    private string $vendorDir;

    protected function setUp(): void
    {
        $this->testModuleDir = sys_get_temp_dir() . '/fa_pm_test_' . uniqid();
        mkdir($this->testModuleDir, 0755, true);
        mkdir($this->testModuleDir . '/vendor', 0755, true);
        
        $this->composerJsonPath = $this->testModuleDir . '/composer.json';
        $this->vendorDir = $this->testModuleDir . '/vendor';
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->testModuleDir);
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testConstructorSetsModuleDir(): void
    {
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        
        $reflection = new \ReflectionProperty($mgr, 'moduleDir');
        $reflection->setAccessible(true);
        
        $this->assertEquals($this->testModuleDir, $reflection->getValue($mgr));
    }

    public function testConstructorWithConfig(): void
    {
        $config = ['dry_run' => true, 'dev_dependencies' => true];
        $mgr = new ComposerDependencyManager($this->testModuleDir, $config);
        
        $reflection = new \ReflectionProperty($mgr, 'config');
        $reflection->setAccessible(true);
        
        $result = $reflection->getValue($mgr);
        $this->assertTrue($result['dry_run']);
        $this->assertTrue($result['dev_dependencies']);
    }

    public function testHasComposerJsonReturnsFalseWhenNotExists(): void
    {
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $this->assertFalse($mgr->hasComposerJson());
    }

    public function testHasComposerJsonReturnsTrueWhenExists(): void
    {
        file_put_contents($this->composerJsonPath, '{}');
        
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $this->assertTrue($mgr->hasComposerJson());
    }

    public function testIsInstalledReturnsFalseWhenNoVendor(): void
    {
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $this->assertFalse($mgr->isInstalled());
    }

    public function testIsInstalledReturnsFalseWhenNoLockFile(): void
    {
        file_put_contents($this->vendorDir . '/autoload.php', '<?php');
        
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $this->assertFalse($mgr->isInstalled());
    }

    public function testIsInstalledReturnsTrueWhenVendorAndLockExist(): void
    {
        file_put_contents($this->vendorDir . '/autoload.php', '<?php');
        file_put_contents($this->testModuleDir . '/composer.lock', '{}');
        
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $this->assertTrue($mgr->isInstalled());
    }

    public function testGetAutoloadPathThrowsWhenNotExists(): void
    {
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Composer autoloader not found');
        $mgr->getAutoloadPath();
    }

    public function testGetAutoloadPathReturnsPathWhenExists(): void
    {
        file_put_contents($this->vendorDir . '/autoload.php', '<?php');
        file_put_contents($this->testModuleDir . '/composer.lock', '{}');
        
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $expected = $this->vendorDir . '/autoload.php';
        $this->assertEquals($expected, $mgr->getAutoloadPath());
    }

    public function testEnsureDependenciesReturnsTrueWithoutComposerJson(): void
    {
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $this->assertTrue($mgr->ensureDependencies());
    }

    public function testGetStatusReturnsArray(): void
    {
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $status = $mgr->getStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('module_dir', $status);
        $this->assertArrayHasKey('composer_json_exists', $status);
        $this->assertArrayHasKey('is_installed', $status);
    }

    public function testGetStatusShowsModuleDir(): void
    {
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $status = $mgr->getStatus();
        
        $this->assertEquals($this->testModuleDir, $status['module_dir']);
    }

    public function testGetStatusShowsNotInstalled(): void
    {
        $mgr = new ComposerDependencyManager($this->testModuleDir);
        $status = $mgr->getStatus();
        
        $this->assertFalse($status['is_installed']);
        $this->assertFalse($status['composer_json_exists']);
    }
}