<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

// Same runtime PSR-4 registration as the module bootstrap.php (composer
// update is blocked locally; the generated autoload map predates the
// vendored ksfraser packages). Uses the registered-loaders API because
// require_once returns true — not the ClassLoader — when PHPUnit (or FA)
// already loaded vendor/autoload.php.
registerVendoredPsr4(dirname(__DIR__));

/**
 * @param string $moduleDir Module root
 * @return void
 */
function registerVendoredPsr4(string $moduleDir): void
{
    if (!class_exists(\Composer\Autoload\ClassLoader::class)) {
        return;
    }

    $prefixes = [];
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
}

require_once __DIR__ . "/stubs.php";

