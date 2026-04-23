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

class PMContainer implements ContainerInterface
{
    private array $services = [];
    private array $instances = [];

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->services[$id])) {
            $this->services[$id] = $this->createService($id);
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        if (isset($this->instances[$id]) || isset($this->services[$id])) {
            return true;
        }

        return in_array($id, $this->getKnownServices(), true);
    }

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

    private function createService(string $id): object
    {
        return match ($id) {
            DatabaseAdapterInterface::class => new FADatabaseAdapter(),
            EmployeeServiceInterface::class => new FAEmployeeService(),
            \Psr\EventDispatcher\EventDispatcherInterface::class => new FAEventDispatcher(),
            \Psr\Log\LoggerInterface::class => new NullLogger(),
            ProjectServiceInterface::class, ProjectService::class => new ProjectService(
                $this->get(DatabaseAdapterInterface::class),
                $this->get(\Psr\EventDispatcher\EventDispatcherInterface::class),
                $this->get(\Psr\Log\LoggerInterface::class),
                $this->get(EmployeeServiceInterface::class)
            ),
            default => throw new \Psr\Container\NotFoundExceptionInterface("Service $id not found"),
        };
    }
}

/**
 * FA Database Adapter
 */
class FADatabaseAdapter implements DatabaseAdapterInterface
{
    public function fetchAssoc(string $sql, array $params = []): ?array
    {
        $sql = $this->prepareSql($sql);
        $result = db_query($sql, "Query failed");

        if ($result === false) {
            return null;
        }

        return db_fetch_assoc($result);
    }

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

    public function executeUpdate(string $sql, array $params = []): int
    {
        $sql = $this->prepareSql($sql);
        $result = db_query($sql, "Update failed");

        return $result ? db_affected_rows($result) : 0;
    }

    public function lastInsertId(): string|false
    {
        return db_insert_id();
    }

    private function prepareSql(string $sql): string
    {
        return str_replace('fa_pm_', TB_PREF . 'fa_pm_', $sql);
    }
}

/**
 * FA Employee Service
 */
class FAEmployeeService implements EmployeeServiceInterface
{
    public function getEmployee(string $employeeId): array
    {
        $sql = "SELECT * FROM " . TB_PREF . "employee WHERE employee_id = " . db_escape($employeeId);
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

    public function employeeExists(string $employeeId): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "employee WHERE employee_id = " . db_escape($employeeId);
        $result = db_query($sql);
        $row = db_fetch_assoc($result);

        return ($row['cnt'] ?? 0) > 0;
    }

    public function getEmployeesByDepartment(string $department): array
    {
        $sql = "SELECT * FROM " . TB_PREF . "employee WHERE department = " . db_escape($department);
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
class FAEventDispatcher implements \Psr\EventDispatcher\EventDispatcherInterface
{
    private array $listeners = [];

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

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = $listener;
    }

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