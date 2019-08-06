<?php
namespace BrainAppeal\CampusEventsConnector\Updates;

use BrainAppeal\CampusEventsConnector\Service\UpdateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

class ImportFieldNamesUpdateWizard extends AbstractUpdate
{

    /** @var UpdateService */
    protected $updateService;

    /**
     * @var string
     */
    protected $title = 'importFieldNamesUpdateWizard';
    /**
     * @var string
     */
    protected $description = 'Migrates the old import fields to the new named ones.';

    public function __construct()
    {
        $this->updateService = GeneralUtility::makeInstance(UpdateService::class);
    }

    /**
     * Checks whether updates are required.
     *
     * @param string $description The description for the update
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $updateCheck = $this->updateService->checkIfUpdateIsNeeded();

        if ($this->isWizardDone()) {
            return false;
        }
        return $updateCheck;
    }

    /**
     * Performs the required update.
     *
     * @param array $databaseQueries Queries done in this update
     * @param string $customMessage Custom message to be displayed after the update process finished
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $updatesPerformed = $this->updateService->performUpdates();
        if ($updatesPerformed === true) {
            $this->markWizardAsDone();
        }
        return $updatesPerformed;
    }
}