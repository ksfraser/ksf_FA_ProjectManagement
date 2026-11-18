<?php

declare(strict_types=1);

/**
 * ksf_FA_ProjectManagement module bootstrap.
 *
 * Loads the Composer autoloader, then registers the PSR-4 prefixes for the
 * vendored ksfraser/ksf-fa-common and ksfraser/ksf-common-db packages. The
 * packages are required in composer.json but their generated autoload map
 * predates the dependency (composer update is blocked locally by unrelated
 * private packages), so the prefixes are registered here at runtime. This is
 * idempotent and survives a future `composer update`, which will add the same
 * prefixes natively.
 *
 * Uses ClassLoader::getRegisteredLoaders() rather than the return value of
 * require_once: when another module (or PHPUnit) has already loaded
 * vendor/autoload.php, require_once returns true instead of the ClassLoader and
 * a naive `$loader instanceof ClassLoader` guard silently skips registration —
 * the exact cross-module duplicate-autoload hazard called out in AGENTS.md.
 *
 * The repositories are built on ksfraser\CommonDb (the shared data-dictionary
 * + query-builder package): the DAOs code against DbConnectionInterface, and
 * FaDbAdapter translates every operation to FA's native db_* calls at runtime.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

$ksfPmRegisterPsr4 = static function (): void {
    if (!class_exists(\Composer\Autoload\ClassLoader::class)) {
        return;
    }

    $moduleDir = __DIR__;
    $prefixes = [
        'ksfraser\\FrontAccounting\\ProjectManagement\\' => $moduleDir . '/src/Ksfraser/FrontAccounting/ProjectManagement',
    ];

    $ksfFaCommon = $moduleDir . '/vendor/ksfraser/ksf-fa-common/src';
    if (is_dir($ksfFaCommon)) {
        $prefixes['ksfraser\\FrontAccounting\\Common\\'] = $ksfFaCommon;
        $prefixes['Ksfraser\\Frontaccounting\\HTML\\'] = $ksfFaCommon . '/HTML';
    }

    $ksfCommonDb = $moduleDir . '/vendor/ksfraser/ksf-common-db/src';
    if (is_dir($ksfCommonDb)) {
        $prefixes['ksfraser\\CommonDb\\'] = $ksfCommonDb;
    }

    foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
        foreach ($prefixes as $prefix => $path) {
            $loader->addPsr4($prefix, $path);
        }
    }
};

$ksfPmRegisterPsr4();