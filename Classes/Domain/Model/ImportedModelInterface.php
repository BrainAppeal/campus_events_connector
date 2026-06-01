<?php

declare(strict_types=1);

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

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

interface ImportedModelInterface extends DomainObjectInterface
{
    /**
     * @return ?string
     */
    public function getCeImportSource(): ?string;

    /**
     * @param ?string $importSource
     * @return ImportedModelInterface
     */
    public function setCeImportSource(?string $importSource): ImportedModelInterface;

    /**
     * @return ?int
     */
    public function getCeImportId(): ?int;

    /**
     * @param ?int $importId
     * @return ImportedModelInterface
     */
    public function setCeImportId(?int $importId): ImportedModelInterface;

    /**
     * @return ?int
     */
    public function getCeImportedAt(): ?int;

    /**
     * @param ?int $importedAt
     * @return ImportedModelInterface
     */
    public function setCeImportedAt(?int $importedAt): ImportedModelInterface;
}
