<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\FrontAccounting\ProjectManagement\Repository\ProjectTypeRepository;
use ksfraser\FrontAccounting\ProjectManagement\Entity\ProjectType;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Traits\DdlCacheTrait;

/**
 * ProjectTypeService — DDL caching + hooks for project type reference data.
 *
 * @see BR-006 (Cross-Module DDL Caching)
 *
 * @since 1.0.0
 */
class ProjectTypeService
{
    use DdlCacheTrait;

    private ProjectTypeRepository $repo;

    /** @var array[]|null Entity cache */
    private static ?array $entityCache = null;

    public function __construct(?ProjectTypeRepository $repo = null)
    {
        $this->repo = $repo ?? new ProjectTypeRepository();
    }

    // ─── Entity Access ─────────────────────────────────────────────

    public function getEntities(bool $activeOnly = true): array
    {
        $key = $activeOnly ? 'active' : 'all';
        if (self::$entityCache !== null && isset(self::$entityCache[$key])) {
            return self::$entityCache[$key];
        }
        $rows = $activeOnly ? $this->repo->findActive() : $this->repo->findAll();
        $entities = [];
        foreach ($rows as $row) {
            $entities[] = new ProjectType($row);
        }
        self::$entityCache[$key] = $entities;
        return $entities;
    }

    public function listAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): ?array
    {
        $entity = $this->repo->findById($id);
        return $entity ? $entity->toArray() : null;
    }

    // ─── DDL (via DdlCacheTrait) ──────────────────────────────────

    public function getHtmlOptions(
        bool $activeOnly = true,
        string $blankLabel = '',
        string $formatString = '{name}',
        int $selectedId = 0
    ): array {
        $dataKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString;

        $options = $this->getOrBuildOptions($dataKey, function () use ($activeOnly, $blankLabel, $formatString) {
            $entities = $this->getEntities($activeOnly);
            $opts = [];
            if ($blankLabel !== '') {
                $opts[] = new HtmlOption('', $blankLabel);
            }
            foreach ($entities as $entity) {
                $text = str_replace(
                    ['{name}', '{id}'],
                    [$entity->getName(), (string)$entity->getId()],
                    $formatString
                );
                $opts[] = new HtmlOption((string)$entity->getId(), $text);
            }
            return $opts;
        });

        if ($selectedId > 0) {
            $cloned = [];
            foreach ($options as $option) {
                $clone = clone $option;
                $clone->setSelected($clone->getValue() === (string)$selectedId);
                $cloned[] = $clone;
            }
            return $cloned;
        }
        return $options;
    }

    public function getDdl(
        bool $activeOnly = true,
        string $blankLabel = '',
        string $formatString = '{name}',
        int $selectedId = 0
    ): array {
        $htmlKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString . '|' . $selectedId;
        $dataKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString;
        $this->getOrBuildOptions($dataKey, function () use ($activeOnly, $blankLabel, $formatString) {
            return $this->getHtmlOptions($activeOnly, $blankLabel, $formatString);
        });
        $options = self::getOptionCacheState()[$dataKey] ?? [];
        return $this->getOrRenderHtml($htmlKey, $options, $selectedId);
    }

    public function getProjectTypeSelect(
        string $name = 'project_type_id',
        bool $activeOnly = true,
        string $blankLabel = '',
        string $formatString = '{name}',
        string $cssClass = 'form-control',
        int $selectedId = 0
    ): HtmlSelect {
        $select = new HtmlSelect($name);
        $select->setClass($cssClass);
        foreach ($this->getHtmlOptions($activeOnly, $blankLabel, $formatString, $selectedId) as $option) {
            $select->addOption($option);
        }
        return $select;
    }

    // ─── CRUD (invalidates cache) ─────────────────────────────────

    public function create(array $data): int
    {
        $id = $this->repo->save($data);
        self::invalidateAllCaches();
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->repo->update($id, $data);
        self::invalidateAllCaches();
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
        self::invalidateAllCaches();
    }

    public static function invalidateAllCaches(): void
    {
        self::$entityCache = null;
        self::invalidateCache();
    }

    // ─── Hook Response Methods ────────────────────────────────────

    public function hookGetProjectTypes(array &$data, $opts = null): array
    {
        $entities = $this->getEntities($data['active_only'] ?? true);
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entity->toArray();
        }
        return $result;
    }

    public function hookGetProjectTypeDDL(array &$data, $opts = null): array
    {
        return $this->getDdl(
            $data['active_only'] ?? true,
            $data['blank_label'] ?? '',
            $data['format'] ?? '{name}',
            $data['selected_id'] ?? 0
        );
    }

    public function hookGetProjectTypeHtmlOptions(array &$data, $opts = null): array
    {
        return $this->getHtmlOptions(
            $data['active_only'] ?? true,
            $data['blank_label'] ?? '',
            $data['format'] ?? '{name}',
            $data['selected_id'] ?? 0
        );
    }
}
