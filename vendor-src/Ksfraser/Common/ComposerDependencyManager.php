<?php

namespace Ksfraser\Common;

/**
 * Composer Dependency Manager - SHARED LIBRARY
 * 
 * Manages composer dependencies for any PHP module system.
 * Single Responsibility: Ensure module dependencies are installed.
 * 
 * Platform-agnostic and reusable across all modules.
 * - Checks if dependencies are installed
 * - Auto-runs composer install if needed
 * - Provides status and error reporting
 * 
 * USAGE in any module:
 *   use Ksfraser\Common\ComposerDependencyManager;
 *   $mgr = new ComposerDependencyManager(__DIR__);
 *   $mgr->ensureDependencies();
 *   require $mgr->getAutoloadPath();
 * 
 * @package Ksfraser\Common
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   2026-04-20
 */
class ComposerDependencyManager
{
    /**
     * Module directory path
     * @var string
     */
    private $moduleDir;
    
    /**
     * Path to composer.json
     * @var string
     */
    private $composerJsonPath;
    
    /**
     * Path to vendor/autoload.php
     * @var string
     */
    private $autoloadPath;
    
    /**
     * Configuration
     * @var array
     */
    private $config;
    
    /**
     * Initialize with module directory
     * 
     * @param string $moduleDir            Module root directory
     * @param array  $config               Configuration options
     *                                     - 'dry_run': bool (default false)
     *                                     - 'dev_dependencies': bool (default false = --no-dev)
     *                                     - 'timeout': int seconds (default 300)
     */
    public function __construct($moduleDir, $config = [])
    {
        $this->moduleDir = rtrim($moduleDir, '/\\');
        $this->composerJsonPath = $this->moduleDir . DIRECTORY_SEPARATOR . 'composer.json';
        $this->autoloadPath = $this->moduleDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        
        $this->config = array_merge([
            'dry_run' => false,
            'dev_dependencies' => false,  // true = include dev, false = --no-dev
            'timeout' => 300,
        ], $config);
    }
    
    /**
     * Ensure composer dependencies are installed
     * 
     * Returns true if already installed or successfully installed.
     * Throws exception if installation fails.
     * 
     * @return bool True if dependencies are available
     * @throws \Exception If dependencies not installed and installation fails
     */
    public function ensureDependencies(): bool
    {
        // Check if composer.json exists
        if (!$this->hasComposerJson()) {
            // No composer.json = assume dependencies in parent or not needed
            return true;
        }
        
        // If vendor/autoload.php exists and composer.lock exists, we're good
        if ($this->isInstalled()) {
            return true;
        }
        
        // Run composer install
        $this->install();
        return true;
    }
    
    /**
     * Check if composer.json exists
     * @return bool
     */
    public function hasComposerJson(): bool
    {
        return file_exists($this->composerJsonPath);
    }
    
    /**
     * Check if composer dependencies are already installed
     * @return bool
     */
    public function isInstalled(): bool
    {
        $hasAutoload = file_exists($this->autoloadPath);
        $hasLock = file_exists($this->moduleDir . DIRECTORY_SEPARATOR . 'composer.lock');
        return $hasAutoload && $hasLock;
    }
    
    /**
     * Get path to autoload.php
     * @return string
     * @throws \Exception If not installed
     */
    public function getAutoloadPath(): string
    {
        if (!file_exists($this->autoloadPath)) {
            throw new \Exception(
                'Composer autoloader not found. Run: composer install in ' . $this->moduleDir
            );
        }
        return $this->autoloadPath;
    }
    
    /**
     * Run composer install
     * 
     * @return string Output from composer
     * @throws \Exception If composer fails or is not available
     */
    public function install(): string
    {
        // Check if shell_exec is available
        if (!$this->canExecuteShellCommands()) {
            throw new \Exception(
                'Cannot run shell commands (shell_exec disabled). ' .
                'Please manually run: cd ' . $this->moduleDir . ' && composer install'
            );
        }
        
        // Build composer command
        $command = $this->buildComposerCommand();
        
        \error_log('COMPOSER: Running: ' . $command);
        
        // Execute composer install
        $output = shell_exec($command);
        
        if ($output === null) {
            throw new \Exception(
                'Composer command failed or returned null. ' .
                'Make sure composer is installed and in PATH. ' .
                'Fallback: cd ' . $this->moduleDir . ' && composer install'
            );
        }
        
        \error_log('COMPOSER: Output: ' . $output);
        
        // Verify installation succeeded
        if (!file_exists($this->autoloadPath)) {
            throw new \Exception(
                'Composer installation failed: vendor/autoload.php not found after running composer install.' .
                "\nOutput: " . $output
            );
        }
        
        return $output;
    }
    
    /**
     * Build the composer install command
     * @return string
     */
    private function buildComposerCommand(): string
    {
        $flags = [];
        
        if (!$this->config['dev_dependencies']) {
            $flags[] = '--no-dev';
        }
        
        // Build command with proper escaping
        $cmd = 'composer install ' . implode(' ', $flags);
        $cmd .= ' --working-dir=' . escapeshellarg($this->moduleDir) . ' 2>&1';
        
        // On non-Windows, check for composer with 'which' first
        if (PHP_OS_FAMILY !== 'Windows') {
            $cmd = 'which composer >/dev/null 2>&1 && ' . $cmd . ' || composer ' . 
                   implode(' ', $flags) . ' --working-dir=' . escapeshellarg($this->moduleDir) . ' 2>&1';
        }
        
        return $cmd;
    }
    
    /**
     * Check if shell commands can be executed
     * @return bool
     */
    private function canExecuteShellCommands(): bool
    {
        return function_exists('shell_exec') && function_exists('exec');
    }
    
    /**
     * Get installation status
     * @return array
     */
    public function getStatus(): array
    {
        return [
            'module_dir' => $this->moduleDir,
            'composer_json_exists' => $this->hasComposerJson(),
            'autoload_path' => $this->autoloadPath,
            'autoload_exists' => file_exists($this->autoloadPath),
            'composer_lock_exists' => file_exists($this->moduleDir . DIRECTORY_SEPARATOR . 'composer.lock'),
            'is_installed' => $this->isInstalled(),
            'shell_exec_available' => $this->canExecuteShellCommands(),
        ];
    }
}