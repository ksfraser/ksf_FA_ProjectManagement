<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Entity;

class ProjectType
{
    private int $id;
    private string $name;
    private ?string $description;
    private bool $inactive;
    private int $sortOrder;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->inactive = (bool)($data['inactive'] ?? 0);
        $this->sortOrder = (int)($data['sort_order'] ?? 0);
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function isInactive(): bool { return $this->inactive; }
    public function isActive(): bool { return !$this->inactive; }
    public function getSortOrder(): int { return $this->sortOrder; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'inactive' => $this->inactive ? 1 : 0,
            'sort_order' => $this->sortOrder,
        ];
    }
}
