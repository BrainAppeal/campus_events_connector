<?php
namespace BrainAppeal\CampusEventsConnector\Updates;

use BrainAppeal\CampusEventsConnector\Service\UpdateService;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class ImportFieldNamesUpdateWizard implements UpgradeWizardInterface
{
    public const IDENTIFIER = 'campusEventsConnector';

    /** @var UpdateService */
    protected $updateService;

    /**
     * @var string
     */
    protected $title = 'Campus Events: Migrate import fields';

    public static $identifier = self::IDENTIFIER;

    /**
     * @var string
     */
    protected $description = 'Campus Events: Migrates the old import fields to the new named ones.';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @return UpdateService
     */
    protected function getUpdateService()
    {
        if (null === $this->updateService) {
            $this->updateService = GeneralUtility::makeInstance(UpdateService::class);
        }
        return $this->updateService;
    }

    /**
     * Returns the title attribute
     *
     * @deprecated Deprecated since TYPO3 v9
     * @return string The title of this update wizard
     */
    public function getTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }
        return self::$identifier;
    }

    /**
     * Sets the title attribute
     *
     * @param string $title The title of this update wizard
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the identifier of this class
     *
     * @return string The identifier of this update wizard
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * Marks some wizard as being "seen" so that it not shown again.
     *
     * Writes the info in LocalConfiguration.php
     *
     * @param mixed $confValue The configuration is set to this value
     */
    protected function markWizardAsDone($confValue = 1)
    {
        GeneralUtility::makeInstance(Registry::class)->set('installUpdate', static::class, $confValue);
    }

    /**
     * Checks if this wizard has been "done" before
     *
     * @return bool TRUE if wizard has been done before, FALSE otherwise
     */
    protected function isWizardDone()
    {
        $wizardClassName = static::class;
        return GeneralUtility::makeInstance(Registry::class)->get('installUpdate', $wizardClassName, false);
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Execute the update
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $updatesPerformed = $this->getUpdateService()->performUpdates();
        if ($updatesPerformed === true) {
            $this->markWizardAsDone();
        }
        return $updatesPerformed;
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $description = '';
        $result = $this->checkForUpdate();
        if (null !== $this->output) {
            $this->output->write($description);
        }
        return $result;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Setter injection for output into upgrade wizards
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Checks whether updates are required.
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function checkForUpdate(): bool
    {
        $updateCheck = $this->getUpdateService()->checkIfUpdateIsNeeded();

        if ($this->isWizardDone()) {
            return false;
        }
        return $updateCheck;
    }

}