<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FA\ProjectManagement;

use PHPUnit\Framework\TestCase;

/**
 * The employee lookups in includes/PMContainer.php and includes/pm_db.inc used
 * to address `TB_PREF . "employee"` and `TB_PREF . "employees"` (and, at
 * pm_db.inc:21, an unprefixed `employees` join). No schema in the estate (FA
 * core included) creates either table, so every project and project-team
 * query raised MySQL error 1146. db_query() only raises when the caller
 * passes a message, so the failure was invisible: the list simply came back
 * empty.
 *
 * The employee directory is module-local
 * (`0_ksf_projectmanagement_employees`) because FA has no employee entity -
 * see the comment in sql/install.sql.
 */
class EmployeeTableTest extends TestCase
{
    private const EMPLOYEE_TABLE = 'ksf_projectmanagement_employees';

    private string $moduleDir;

    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }

    /**
     * @dataProvider employeeQueryProvider
     */
    public function testEmployeeQueriesDoNotAddressTheNonExistentEmployeeTables(string $file): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/' . $file);

        $this->assertStringNotContainsString(
            'TB_PREF . "employee ',
            $contents,
            $file . ' must not address 0_employee: that table is created by no schema'
        );
        $this->assertStringNotContainsString(
            'TB_PREF . "employees ',
            $contents,
            $file . ' must not address 0_employees: that table is created by no schema'
        );
        $this->assertStringNotContainsString(
            'JOIN employees ',
            $contents,
            $file . ' must not join an unprefixed employees table'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function employeeQueryProvider(): array
    {
        return [
            'PMContainer' => ['includes/PMContainer.php'],
            'pm_db'      => ['includes/pm_db.inc'],
        ];
    }

    public function testEmployeeQueriesUseTheModuleLocalEmployeeDirectory(): void
    {
        foreach (['includes/PMContainer.php', 'includes/pm_db.inc'] as $file) {
            $contents = (string) file_get_contents($this->moduleDir . '/' . $file);

            $this->assertStringContainsString(
                'TB_PREF . "' . self::EMPLOYEE_TABLE,
                $contents,
                $file . ' must read the module-owned employee directory'
            );
        }
    }

    public function testSchemaCreatesTheEmployeeDirectory(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/sql/install.sql');

        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `0_' . self::EMPLOYEE_TABLE . '`',
            $contents,
            'sql/install.sql must create the table the code queries'
        );
    }

    public function testEmployeeDirectoryCarriesTheColumnsTheQueriesSelect(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/sql/install.sql');

        foreach (['employee_id', 'first_name', 'last_name', 'email', 'job_title', 'department'] as $column) {
            $this->assertStringContainsString('`' . $column . '`', $contents);
        }
    }
}
