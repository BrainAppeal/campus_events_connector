<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport\DataTransformer;

use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\CeApiConnector;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\AbstractDataTransformer;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileMappingModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;

class DefaultDataTransformer extends AbstractDataTransformer
{
    /**
     * References to other records can either be an array with @id set ot a reference uri to a single record.
     * We pre-process these to only have ID values in the array.
     * @var array<string, ImportFieldConfigurationModel[]>
     */
    private array $mapFieldsWithReferences = [];

    public function __construct(protected ImportTableConfigurationModel $importConfiguration)
    {
        parent::__construct($importConfiguration);
        if (!empty($this->importConfiguration->getDependenciesToOtherTables())) {
            $fieldMap = $this->importConfiguration->getImportFieldMap();
            foreach ($fieldMap as $mapEntry) {
                if ($mapEntry->isReference()) {
                    $fieldName = $mapEntry->getSourceField();
                    $this->mapFieldsWithReferences[$fieldName] = $mapEntry;
                }
            }
        }
    }

    public function postProcessAfterModelAdded(ImportRecordModel $model): void
    {
        if (empty($this->mapFieldsWithReferences)) {
            return;
        }
        $importData = $model->getImportData();
        // Convert references to IDs
        foreach ($this->mapFieldsWithReferences as $fieldName => $mapEntry) {
            if (!array_key_exists($fieldName, $importData)) {
                throw new \InvalidArgumentException(sprintf('Missing required field in import data: %s for table %s', $fieldName, $this->getTable()));
            }
            /** @var ImportFieldConfigurationModel $mapEntry */
            $value = $importData[$fieldName]??null;
            $procVal = null;
            if (is_array($value)) {
                if (!empty($value['@id'])) {
                    $procVal = CeApiConnector::filterId($value['@id']);
                } else {
                    $procVal = [];
                    foreach ($value as $item) {
                        if (is_array($item) && !empty($item['@id'])) {
                            $procVal[] = CeApiConnector::filterId($item['@id']);
                        } elseif (is_scalar($item)) {
                            $procVal[] = CeApiConnector::filterId($item);
                        }
                    }
                    $procVal = array_filter($procVal);
                    $mmTable = $mapEntry->get('mm_table');
                    if (!$mmTable) {
                        $procVal = implode(',', $procVal);
                    }
                }
            } elseif($value !== null) {
                $procVal = CeApiConnector::filterId((string)$value);
            }
            $importData[$fieldName] = $procVal;
        }
        // No post-processing required
        $model->updateImportData($importData);
    }

    public function isApiListItemContainsAllData(): bool
    {
        return $this->importConfiguration->isApiListItemContainsAllData();
    }

    public function postProcessConvertedData(ImportRecordModel $model, array $data): array
    {
        $externalResourceUrl = $data['external_resource_url'] ?? null;
        if ($externalResourceUrl && !str_starts_with($externalResourceUrl, 'http')
            && $this->hasFileTransformations() && $baseUri = $this->getFileDataTransformerHelper()->getBaseUri()) {
            $data['external_resource_url'] = rtrim($baseUri, '/') . '/' . ltrim($externalResourceUrl, '/');
        }
        return $data;
    }

    public function getApiEndpoint(): ?string
    {
        $apiEndpoint = $this->importConfiguration->getApiEndpoint();
        if (empty($apiEndpoint)) {
            throw new \RuntimeException('Api endpoint not set for ' . $this->getTable(), 1767112698);
        }
        return $apiEndpoint;
    }

    public function getEntityName(): string
    {
        $importField = $this->importConfiguration->getImportField();
        if (empty($importField)) {
            throw new \RuntimeException('Entity name not set for ' . $this->getTable(), 1766859355);
        }
        return $importField;
    }

    public static function getApiField(): ?string
    {
        return '@id';
    }

    /**
     * Optionally, set the force update flag for the file import model
     *
     * @param ImportRecordModel $model
     * @param ImportFileMappingModel $fileModel
     * @return void
     */
    protected function checkIfForcedFileUpdateIsRequired(ImportRecordModel $model, ImportFileMappingModel $fileModel): void
    {
        // Check if the external resource url for event images and attachments and sponsor images has changed
        $changedValuesBeforeUpdate = $model->getChangedValuesBeforeUpdate();
        $oldExternalResourceUrl = $changedValuesBeforeUpdate['external_resource_url'] ?? null;
        if (!empty($oldExternalResourceUrl)) {
            $transformedData = $model->getTransformedData();
            $currentExternalResourceUrl = $transformedData['external_resource_url'] ?? null;
            if ($oldExternalResourceUrl !== $currentExternalResourceUrl) {
                $fileModel->setIsForceUpdate(true);
            }
        }
    }
}
