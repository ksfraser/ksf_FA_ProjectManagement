<?php

declare(strict_types=1);

namespace Ksfraser\Tests;

/**
 * Runs a snippet of PHP in a child interpreter.
 *
 * Some shipped classes in this module cannot be loaded at all (a wrong `use`
 * spelling for the WorkflowHooksTrait makes the class declaration raise a PHP
 * fatal, which is unrecoverable in-process). Instantiating one of them from a
 * test would abort the entire PHPUnit run rather than fail one test, so the
 * behaviour of such a class is probed out-of-process instead and the child's
 * stdout/stderr become the assertion subject.
 *
 * The child gets the module's real bootstrap (vendor/autoload.php) plus
 * tests/stubs.php, so the FA globals and the GLOBALS-backed fake DB behave
 * exactly as they do in-process.
 *
 * PHP 7.4 compatible.
 *
 * @BABOK Related: BR-PM-010 (test harness)
 */
final class ChildPhpRunner
{
    /**
     * Execute $body in a fresh PHP process and return its result.
     *
     * @param string $body PHP statements (no opening tag).
     * @return array{exit:int,output:string}
     */
    public static function run(string $body): array
    {
        $file = sys_get_temp_dir()
            . '/pm_child_' . getmypid() . '_' . str_replace('.', '_', uniqid('', true)) . '.php';

        file_put_contents($file, "<?php\n" . self::preamble() . "\n" . $body . "\n");

        $lines = [];
        $exit  = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=stderr ' . escapeshellarg($file) . ' 2>&1', $lines, $exit);

        @unlink($file);

        return ['exit' => $exit, 'output' => implode("\n", $lines)];
    }

    /**
     * The single most informative line of a child's output.
     *
     * With xdebug enabled the raw output is a stack trace plus a call table;
     * the failure message only needs the sentence that explains the failure.
     *
     * @param string $output
     * @return string
     */
    public static function diagnostic(string $output): string
    {
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (preg_match('/(Fatal error|Parse error|Uncaught \w+|[A-Za-z]+Error:|Warning:)/', $line) === 1) {
                return $line;
            }
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));

        return $lines === [] ? '(child produced no output)' : $lines[0];
    }

    /**
     * Run $body and decode the JSON it echoed.
     *
     * A non-zero exit means the snippet died (fatal, uncaught throw); the
     * caller is expected to assert on that rather than on the payload.
     *
     * @param string $body
     * @return array{exit:int,output:string,data:mixed}
     */
    public static function runJson(string $body): array
    {
        $marker = '###PMJSON###';
        $result = self::run($body . "\necho '" . $marker . "' . json_encode(\$RESULT);");

        $pos = strrpos($result['output'], $marker);
        $raw = $pos === false ? '' : substr($result['output'], $pos + strlen($marker));

        return [
            'exit'   => $result['exit'],
            'output' => $result['output'],
            'data'   => $raw === '' ? null : json_decode($raw, true),
        ];
    }

    /**
     * The same bootstrap tests/bootstrap.php builds, minus the PHPUnit runner.
     *
     * @return string
     */
    private static function preamble(): string
    {
        $tests = dirname(__DIR__) . '/tests';
        $root  = dirname(__DIR__);

        return "require " . var_export($root . '/vendor/autoload.php', true) . ";\n"
            . "require " . var_export($tests . '/stubs.php', true) . ";\n";
    }
}