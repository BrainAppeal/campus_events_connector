<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2021 Brain Appeal GmbH
 *
 * @copyright 2021 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */


namespace BrainAppeal\CampusEventsConnector\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * AbstractImportedEntity
 */
abstract class AbstractImportedEntity extends AbstractEntity implements ImportedModelInterface
{
    use ImportedModelTrait;

    protected ?bool $overrideIsDirty = null;

    public function _isDirty(?string $propertyName = null): bool
    {
        if ($propertyName === null && $this->overrideIsDirty !== null) {
            return $this->overrideIsDirty;
        }
        return parent::_isDirty($propertyName);
    }

    /**
     * Checks if the object or any of its associated properties is in a "dirty" state recursively.
     * A property is considered dirty if its state has changed compared to the last known clean state.
     *
     * @param int $level The current recursion depth. Used to prevent infinite recursion. Defaults to 0.
     * @return bool Returns true if the object or any of its associated properties is dirty, false otherwise.
     */
    public function objectIsDirtyDeep($level = 0): bool
    {
        if ($this->_isDirty()) {
            if ($level > 1) {
                return true;
            }
            $dirtyProperties = [];
            foreach ($this->_getCleanProperties() as $propertyName => $cleanPropertyValue) {
                if (parent::_isDirty($propertyName)) {
                    $isDirty = true;
                    $currentPropertyValue = $this->_getProperty($propertyName);
                    if ($cleanPropertyValue instanceof ObjectStorage) {
                        if ($currentPropertyValue instanceof ObjectStorage
                            && $currentPropertyValue->count() === $cleanPropertyValue->count()) {
                            foreach ($cleanPropertyValue as $child) {
                                /** @var AbstractDomainObject $child */
                                if (($child instanceof AbstractImportedEntity) && !$child->objectIsDirtyDeep($level + 1)) {
                                    $isDirty = false;
                                }
                            }
                        }
                    } elseif ($cleanPropertyValue instanceof AbstractImportedEntity) {
                        $isDirty = $cleanPropertyValue->objectIsDirtyDeep($level + 1);
                    }
                    if ($isDirty) {
                        $dirtyProperties[$propertyName] = true;
                    }
                }
            }
            if (!empty($dirtyProperties)) {
                return true;
            }
            $this->overrideIsDirty = false;
        }
        return false;
    }
}
