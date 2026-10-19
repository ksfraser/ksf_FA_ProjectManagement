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
  *
 * @BABOK Related: BR-006 — cross-module DDL caching — the three-layer option/DDL cache and its invalidation hooks
 */
class ProjectTypeService
{
    use DdlCacheTrait;

    private ProjectTypeRepository $repo;

    /** @var array[]|null Entity cache */
    private static ?array $entityCache = null;

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching
     */
    public function __construct(?ProjectTypeRepository $repo = null)
    {
        $this->repo = $repo ?? new ProjectTypeRepository();
    }

    // ─── Entity Access ─────────────────────────────────────────────

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching — layer 1: entity cache, keyed active/all
     */
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

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching
     */
    public function listAll(): array
    {
        return $this->repo->findAll();
    }

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching
     */
    public function getById(int $id): ?array
    {
        $entity = $this->repo->findById($id);
        return $entity ? $entity->toArray() : null;
    }

    // ─── DDL (via DdlCacheTrait) ──────────────────────────────────

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching — layer 2: HtmlOption array cache
     */
    public function getHtmlOptions(
        bool $activeOnly = true,
        string $blankLabel = '',
        string $formatString = '{name}',
        int $selectedId = 0
    ): array {
        $dataKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString;

        /**
         * @BABOK Related: BR-006 — cross-module DDL caching — layer 1: entity cache, keyed active/all
         */
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

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching — layer 3: DDL string cache
     */
    public function getDdl(
        bool $activeOnly = true,
        string $blankLabel = '',
        string $formatString = '{name}',
        int $selectedId = 0
    ): array {
        $htmlKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString . '|' . $selectedId;
        $dataKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString;
        /**
         * @BABOK Related: BR-006 — cross-module DDL caching — layer 2: HtmlOption array cache
         */
        $this->getOrBuildOptions($dataKey, function () use ($activeOnly, $blankLabel, $formatString) {
            return $this->getHtmlOptions($activeOnly, $blankLabel, $formatString);
        });
        $options = self::getOptionCacheState()[$dataKey] ?? [];
        return $this->getOrRenderHtml($htmlKey, $options, $selectedId);
    }

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching — renders a select from the cache
     */
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

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching — a write invalidates every layer
     */
    public function create(array $data): int
    {
        $id = $this->repo->save($data);
        self::invalidateAllCaches();
        return $id;
    }

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching — a write invalidates every layer
     */
    public function update(int $id, array $data): void
    {
        $this->repo->update($id, $data);
        self::invalidateAllCaches();
    }

    /**
     * @BABOK Related: BR-006 — cross-module DDL caching — a write invalidates every layer
     */
    public function delete(int $id): void
    {
        $this->repo->delete($id);
        self::invalidateAllCaches();
    }

    /**
     * @BABOK Related: FR-006-004 — cache invalidation
     */
    public static function invalidateAllCaches(): void
    {
        self::$entityCache = null;
        self::invalidateCache();
    }

    // ─── Hook Response Methods ────────────────────────────────────

    /**
     * @BABOK Related: FR-006-005 — hook contract returning the cached arrays
     */
    public function hookGetProjectTypes(array &$data, $opts = null): array
    {
        $entities = $this->getEntities($data['active_only'] ?? true);
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entity->toArray();
        }
        return $result;
    }

    /**
     * @BABOK Related: FR-006-005 — hook contract returning the DDL strings
     */
    public function hookGetProjectTypeDDL(array &$data, $opts = null): array
    {
        return $this->getDdl(
            $data['active_only'] ?? true,
            $data['blank_label'] ?? '',
            $data['format'] ?? '{name}',
            $data['selected_id'] ?? 0
        );
    }

    /**
     * @BABOK Related: FR-006-005 — hook contract returning HtmlOption objects
     */
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
