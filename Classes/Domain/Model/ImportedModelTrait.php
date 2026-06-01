<?php

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2026 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Domain\Model;

trait ImportedModelTrait
{
    /**
     * importSource
     *
     * @var ?string
     */
    protected $ceImportSource = '';

    /**
     * importId
     *
     * @var ?int
     */
    protected $ceImportId = 0;

    /**
     * importedAt
     *
     * @var ?int
     */
    protected $ceImportedAt = 0;

    /**
     * @return ?string
     */
    public function getCeImportSource(): ?string
    {
        return $this->ceImportSource;
    }

    /**
     * @param ?string $importSource
     * @return ImportedModelInterface
     */
    public function setCeImportSource(?string $importSource): ImportedModelInterface
    {
        $this->ceImportSource = $importSource;

        return $this;
    }

    /**
     * @return ?int
     */
    public function getCeImportId(): ?int
    {
        return $this->ceImportId;
    }

    /**
     * @param ?int $importId
     * @return ImportedModelInterface
     */
    public function setCeImportId(?int $importId): ImportedModelInterface
    {
        $this->ceImportId = $importId;

        return $this;
    }

    /**
     * @return ?int
     */
    public function getCeImportedAt(): ?int
    {
        return $this->ceImportedAt;
    }

    /**
     * @param ?int $importedAt
     * @return ImportedModelInterface
     */
    public function setCeImportedAt(?int $importedAt): ImportedModelInterface
    {
        $this->ceImportedAt = $importedAt;

        return $this;
    }
}
