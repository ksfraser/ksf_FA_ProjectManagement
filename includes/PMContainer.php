<?php
/**
 * FA PM Dependency Injection Container
 *
 * @package Ksfraser\ProjectManagement\FA
 */

declare(strict_types=1);

namespace Ksfraser\ProjectManagement\FA;

use Ksfraser\ProjectManagement\Contract\DatabaseAdapterInterface;
use Ksfraser\ProjectManagement\Contract\EmployeeServiceInterface;
use Ksfraser\ProjectManagement\Contract\ProjectServiceInterface;
use Ksfraser\ProjectManagement\ProjectService;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * @BABOK Related: N/A — the composition root: DI wiring only, no behaviour of its own
 */
class PMContainer implements ContainerInterface
{
    private array $services = [];
    private array $instances = [];

    /**
     * PSR-11 declares get() with no return type; psr/container 2.0 adds
     * `: mixed`, which PHP 7.4 cannot express — 7.4 parses `mixed` as a *class*
     * name, so the declaration silently becomes "instance of class mixed" and
     * every call raises a TypeError. Declare it in PHPDoc instead.
     *
     * @return object
      *
 * @BABOK Related: N/A — container lookup
 */
    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->services[$id])) {
            $this->services[$id] = $this->createService($id);
        }

        return $this->services[$id];
    }

    /**
     * @BABOK Related: N/A — container lookup
     */
    public function has(string $id): bool
    {
        if (isset($this->instances[$id]) || isset($this->services[$id])) {
            return true;
        }

        return in_array($id, $this->getKnownServices(), true);
    }

    /**
     * @BABOK Related: N/A — container introspection
     */
    private function getKnownServices(): array
    {
        return [
            ProjectServiceInterface::class,
            ProjectService::class,
            DatabaseAdapterInterface::class,
            EmployeeServiceInterface::class,
            \Psr\EventDispatcher\EventDispatcherInterface::class,
            \Psr\Log\LoggerInterface::class,
        ];
    }

    /**
     * @BABOK Related: N/A — lazy construction
     */
    private function createService(string $id): object
    {
        switch ($id) {
            case DatabaseAdapterInterface::class:
                return new FADatabaseAdapter();
            case EmployeeServiceInterface::class:
                return new FAEmployeeService();
            case \Psr\EventDispatcher\EventDispatcherInterface::class:
                return new FAEventDispatcher();
            case \Psr\Log\LoggerInterface::class:
                return new NullLogger();
            case ProjectServiceInterface::class:
            case ProjectService::class:
                return new ProjectService(
                    $this->get(DatabaseAdapterInterface::class),
                    $this->get(\Psr\EventDispatcher\EventDispatcherInterface::class),
                    $this->get(\Psr\Log\LoggerInterface::class),
                    $this->get(EmployeeServiceInterface::class)
                );
            default:
                throw new \Psr\Container\NotFoundExceptionInterface("Service $id not found");
        }
    }
}

/**
 * FA Database Adapter
 */
/**
 * @BABOK Related: N/A — the FA-side DbConnectionInterface adapter. Not a requirement: it is the transport rule (runtime code goes through FaDbAdapter, which calls native db_*; PdoDbAdapter is never used here).
 */
class FADatabaseAdapter implements DatabaseAdapterInterface
{
    /**
     * @BABOK Related: N/A — DbConnectionInterface pass-through
     */
    public function fetchAssoc(string $sql, array $params = []): ?array
    {
        $sql = $this->prepareSql($sql);
        $result = db_query($sql, "Query failed");

        if ($result === false) {
            return null;
        }

        return db_fetch_assoc($result);
    }

    /**
     * @BABOK Related: N/A — DbConnectionInterface pass-through
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $sql = $this->prepareSql($sql);
        $result = db_query($sql, "Query failed");

        if ($result === false) {
            return [];
        }

        $rows = [];
        while ($row = db_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @BABOK Related: N/A — DbConnectionInterface pass-through
     */
    public function executeUpdate(string $sql, array $params = []): int
    {
        $sql = $this->prepareSql($sql);
        $result = db_query($sql, "Update failed");

        return $result ? db_affected_rows($result) : 0;
    }

    /**
     * @BABOK Related: N/A — DbConnectionInterface pass-through
     */
    public function lastInsertId()
    {
        return db_insert_id();
    }

    /**
     * @BABOK Related: N/A — routes every query through FaDbAdapter (native db_*); not a requirement, it is the transport rule
     */
    private function prepareSql(string $sql): string
    {
        return str_replace('fa_pm_', TB_PREF . 'fa_pm_', $sql);
    }
}

/**
 * FA Employee Service
 */
/**
 * @BABOK Related: N/A — the FA-side EmployeeServiceInterface bridge to HRM; consumes an external contract rather than implementing a PM requirement.
 */
class FAEmployeeService implements EmployeeServiceInterface
{
    /**
     * @BABOK Related: N/A — EmployeeServiceInterface pass-through to HRM
     */
    public function getEmployee(string $employeeId): array
    {
        $sql = "SELECT * FROM " . TB_PREF . "ksf_projectmanagement_employees WHERE employee_id = " . db_escape($employeeId);
        $result = db_query($sql);

        if ($result === false) {
            throw new \Ksfraser\ProjectManagement\Exception\ProjectException("Employee not found: $employeeId");
        }

        $employee = db_fetch_assoc($result);

        if (!$employee) {
            throw new \Ksfraser\ProjectManagement\Exception\ProjectException("Employee not found: $employeeId");
        }

        return $employee;
    }

    /**
     * @BABOK Related: N/A — EmployeeServiceInterface pass-through to HRM
     */
    public function employeeExists(string $employeeId): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "ksf_projectmanagement_employees WHERE employee_id = " . db_escape($employeeId);
        $result = db_query($sql);
        $row = db_fetch_assoc($result);

        return ($row['cnt'] ?? 0) > 0;
    }

    /**
     * @BABOK Related: N/A — EmployeeServiceInterface pass-through to HRM
     */
    public function getEmployeesByDepartment(string $department): array
    {
        $sql = "SELECT * FROM " . TB_PREF . "ksf_projectmanagement_employees WHERE department = " . db_escape($department);
        $result = db_query($sql);

        $employees = [];
        while ($row = db_fetch_assoc($result)) {
            $employees[] = $row;
        }

        return $employees;
    }
}

/**
 * FA Event Dispatcher
 */
/**
 * @BABOK Related: N/A — the FA-side PSR-14 dispatcher bridge; consumes an external contract rather than implementing a PM requirement.
 */
class FAEventDispatcher implements \Psr\EventDispatcher\EventDispatcherInterface
{
    private array $listeners = [];

    /**
     * @BABOK Related: N/A — PSR-14 dispatch pass-through
     */
    public function dispatch(object $event): object
    {
        $eventName = get_class($event);

        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                $listener($event);

                if ($event instanceof \Psr\EventDispatcher\Stoppable && $event->isPropagationStopped()) {
                    break;
                }
            }
        }

        return $event;
    }

    /**
     * @BABOK Related: N/A — PSR-14 listener registration
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    /**
     * @BABOK Related: N/A — PSR-14 subscriber registration
     */
    public function addSubscriber(\Psr\EventDispatcher\EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $method) {
            if (is_array($method)) {
                $listener = [$subscriber, $method[0]];
                $priority = $method[1] ?? 0;
            } else {
                $listener = [$subscriber, $method];
                $priority = 0;
            }

            $this->addListener($eventName, $listener, $priority);
        }
    }
}