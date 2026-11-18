<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Entity;

/**
 * Link between an imported FA sales order and a project.
 *
 * @BABOK Related: N/A — no requirement defined in this repo for sales-order
 *   linkage or revenue recognition. Neither FR-PM-009 nor FR-PM-010 matches a
 *   ProjectDcs document here (the FA-side FR-PM-010 is the app-shell/tab surface,
 *   a different subject).
 * @since 1.0.0
 */
class ProjectSalesOrder
{
    private int $id;
    private string $projectId;
    private int $faOrderNo;
    private int $faTransType;
    private string $source;
    private ?string $sourceOrderId;
    private string $linkedAt;

    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->projectId = (string)$data['project_id'];
        $this->faOrderNo = (int)($data['fa_order_no'] ?? 0);
        $this->faTransType = (int)($data['fa_trans_type'] ?? 10);
        $this->source = (string)($data['source'] ?? 'all');
        $this->sourceOrderId = $data['source_order_id'] ?? null;
        $this->linkedAt = (string)($data['linked_at'] ?? '');
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function getId(): int { return $this->id; }
    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function getProjectId(): string { return $this->projectId; }
    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function getFaOrderNo(): int { return $this->faOrderNo; }
    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function getFaTransType(): int { return $this->faTransType; }
    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function getSource(): string { return $this->source; }
    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function getSourceOrderId(): ?string { return $this->sourceOrderId; }
    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function getLinkedAt(): string { return $this->linkedAt; }

    /**
     * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'fa_order_no' => $this->faOrderNo,
            'fa_trans_type' => $this->faTransType,
            'source' => $this->source,
            'source_order_id' => $this->sourceOrderId,
            'linked_at' => $this->linkedAt,
        ];
    }
}
