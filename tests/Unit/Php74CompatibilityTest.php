<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use PHPUnit\Framework\TestCase;

/**
 * Guards the module against PHP 8-only syntax.
 *
 * The FA runtime is PHP 7.4.33, but the unit suite executes on whatever PHP
 * the developer happens to run it with (8.1 here), where PHP 8 syntax parses
 * cleanly. That mismatch let `includes/pm_db.inc` ship two PHP 8 union return
 * types (`: object|false`), which are a *parse* error on 7.4: every module
 * page include_once'd the file and died before rendering, while the suite
 * stayed green. `php -l` on the host cannot catch it either, for the same
 * reason.
 *
 * So this test scans the shipped module source for constructs that PHP 7.4
 * cannot parse. Comments and string literals are stripped via the tokenizer
 * first, so prose such as "is never emitted" in a docblock cannot trip it.
 *
 * @BABOK Related: BR-PM-010
 */
class Php74CompatibilityTest extends TestCase
{
    private string $moduleDir;

    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }

    /**
     * Constructs PHP 7.4.33 rejects with a *parse* error, so the file cannot
     * even be loaded. Verified empirically against the real 7.4.33 container,
     * not assumed — `mixed` and `never` are deliberately absent because 7.4
     * parses those as ordinary class names (see runtimeLandmines()).
     *
     * Patterns are applied to tokenizer-stripped source (no comments/strings).
     *
     * @return array<string,string>
      *
 * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
 */
    private function forbiddenConstructs(): array
    {
        return [
            // 8.0 union types, return position: `function f(): object|false`
            'union return type'  => '/\)\s*:\s*\??[A-Za-z_\\\\][A-Za-z0-9_\\\\]*\s*\|/',
            // 8.0 union types, parameter position: `function f(int|string $x)`
            'union param type'   => '/function\s+&?\w+\s*\(\s*\??[A-Za-z_\\\\][A-Za-z0-9_\\\\]*\s*\|/',
            // 8.0 nullsafe operator
            'nullsafe operator'  => '/\?->/',
            // 8.0 match expression
            'match expression'   => '/(?<![\w$])match\s*\(/',
            // 8.1 enum declaration
            'enum declaration'   => '/(?<![\w$])enum\s+[A-Za-z_]/',
            // 8.0 constructor property promotion
            'ctor promotion'     => '/function\s+__construct\s*\([^)]*\b(?:public|private|protected)\b/',
            // 8.0 static return type
            'static return type' => '/\)\s*:\s*static\b/',
            // 8.1 readonly property
            'readonly property'  => '/\breadonly\s+\$|\breadonly\s+[A-Za-z_\\\\]/',
        ];
    }

    /**
     * Constructs that *parse* on 7.4 but are broken there, so `php -l` and a
     * parse-only scan both miss them. On 7.4 these are not reserved words, so
     * the parser reads them as class names: `: mixed` becomes "must be an
     * instance of class mixed" and every call raises a TypeError, including
     * when an object is returned.
     *
     * @return array<string,string>
      *
 * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
 */
    private function runtimeLandmines(): array
    {
        return [
            'mixed return type' => '/\)\s*:\s*mixed\b/',
            'never return type' => '/\)\s*:\s*never\b/',
        ];
    }

    /**
     * Every non-vendor PHP source file the module ships and executes at runtime.
     * Both .php and .inc are scanned: the legacy includes/*.inc helpers are
     * runtime code and are exactly where PHP 8 syntax went unnoticed, because
     * `find -name '*.php'` style tooling skips them.
     *
     * @return string[]
      *
 * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
 */
    private function shippedSources(): array
    {
        $found    = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->moduleDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $path = $file->getPathname();
            $ext  = strtolower($file->getExtension());
            if ($ext !== 'php' && $ext !== 'inc') {
                continue;
            }
            if (strpos($path, '/vendor/') !== false || strpos($path, '/.git/') !== false) {
                continue;
            }
            $found[] = $path;
        }

        sort($found);

        return $found;
    }

    /**
     * Source with comments and string literals blanked out, so only real code
     * is matched. Byte offsets are preserved (replaced spans keep their length)
     * so line numbers stay accurate.
      *
 * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
 */
    private function codeOnly(string $source): string
    {
        /**
         * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
         */
        $blank  = static function (string $text): string {
            return preg_replace('/[^\n]/', ' ', $text);
        };
        $result = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                [$id, $text] = $token;
                if ($id === T_COMMENT || $id === T_DOC_COMMENT || $id === T_CONSTANT_ENCAPSED_STRING
                    || $id === T_ENCAPSED_AND_WHITESPACE || $id === T_INLINE_HTML
                ) {
                    $result .= $blank($text);
                    continue;
                }
            }
            $result .= is_array($token) ? $token[1] : $token;
        }

        return $result;
    }

    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testModuleShipsSourcesToScan(): void
    {
        // Guards against the scanner silently matching nothing.
        $this->assertGreaterThan(20, count($this->shippedSources()));
    }

    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testNoPhp8OnlySyntaxIsShipped(): void
    {
        $violations = [];

        foreach ($this->shippedSources() as $path) {
            $source = file_get_contents($path);
            $this->assertIsString($source, "unreadable source: $path");
            $code  = $this->codeOnly($source);
            $short = $this->relative($path);

            foreach ($this->forbiddenConstructs() + $this->runtimeLandmines() as $label => $pattern) {
                if (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE) !== 1) {
                    continue;
                }
                $line = substr_count(substr($code, 0, $m[0][1]), "\n") + 1;
                $violations[] = sprintf('%s:%d [%s] %s', $short, $line, $label, trim($m[0][0]));
            }
        }

        $this->assertSame(
            [],
            $violations,
            "PHP 8-only syntax shipped; the FA runtime is PHP 7.4.33:\n  "
            . implode("\n  ", $violations)
        );
    }

    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    private function relative(string $path): string
    {
        return ltrim(str_replace($this->moduleDir, '', $path), '/');
    }
}