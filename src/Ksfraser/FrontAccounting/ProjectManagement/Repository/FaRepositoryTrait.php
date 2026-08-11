<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

/**
 * Thin wrappers over FrontAccounting DB globals for repository classes.
 *
 * @since 1.0.0
 */
trait FaRepositoryTrait
{
    protected function dbQuery(string $sql)
    {
        return db_query($sql);
    }

    protected function dbFetchAssoc($result): ?array
    {
        if ($result && db_num_rows($result)) {
            return db_fetch_assoc($result);
        }
        return null;
    }

    protected function dbFetchAll($result): array
    {
        $rows = [];
        if ($result) {
            while ($row = db_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    protected function dbInsertId(): int
    {
        return (int)db_insert_id();
    }

    protected function escape($value): string
    {
        return db_escape($value);
    }

    protected function intVal($value): int
    {
        return (int)$value;
    }

    protected function floatVal($value): float
    {
        return (float)$value;
    }
}
