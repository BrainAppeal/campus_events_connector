<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Command;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowConfigurationCommand extends Command
{
    public function __construct(protected readonly DataTransformerFactory $dataTransformerFactory,)
    {
        parent::__construct();
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $groupKeys = implode(', ', $this->dataTransformerFactory->getRegisteredGroupKeys());
        $this
            ->setHelp('
Shows information about the import configuration.

Import sources: ' . $groupKeys . '
')
            ->addArgument('import-group-or-table', InputArgument::OPTIONAL, 'The import group or table to show configuration for. If omitted, all groups are shown.')
            ->addArgument('target-field', InputArgument::OPTIONAL, 'The target field to show complete configuration for (requires table name as first argument).');
    }

    /**
     * Shows a table with all configured sites
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $importGroupKeys = $this->dataTransformerFactory->getRegisteredGroupKeys();
        if (empty($importGroupKeys)) {
            $io->title('No import groups configured');
            $io->note('Configure new import groups in the TCA configuration');
            return Command::SUCCESS;
        }
        $groupKey = null;
        $table = null;
        $targetField = null;
        if ($key = $input->getArgument('import-group-or-table')) {
            if (in_array($key, $importGroupKeys, false)) {
                $groupKey = $key;
            } else {
                $table = $key;
                // We need to find the group key for the table first
                foreach ($importGroupKeys as $checkGroupKey) {
                    $this->dataTransformerFactory->setGroupKey($checkGroupKey);
                    if ($this->dataTransformerFactory->hasImportConfigurationForTable($table)) {
                        $groupKey = $checkGroupKey;
                        break;
                    }
                }
            }
        }
        if ($table && ($field = $input->getArgument('target-field'))) {
            $targetField = $field;
        }

        if ($groupKey) {
            $this->dataTransformerFactory->setGroupKey($groupKey);
        }

        $io->title('Import configuration details');
        if ($table) {
            if ($targetField) {
                $io->section('Complete configuration for field "' . $table . '.' . $targetField . '"');
                $this->showCompleteFieldConfiguration($io, $table, $targetField);
            } else {
                $io->section('Configuration for table "' . $table . '"');
                $this->showGeneralTableConfiguration($io, $table);
                $io->section('Field configuration for table "' . $table . '"');
                $this->showTableImportFieldsConfiguration($output, $table);
            }
        } elseif($groupKey) {
            $io->section('All configured tables and fields for import group "' . $groupKey . '"');
            $this->showImportGroupTableConfiguration($output, $groupKey);
        } else {
            $io->section('All configured import groups and tables');
            $this->showImportTablesByGroupAsTree($io);
        }


        return Command::SUCCESS;
    }

    /**
     * Displays the import tables grouped by their respective group keys in a tree-like tabular format.
     *
     * @param OutputInterface $output An OutputInterface instance used to render the table.
     *
     * @return void
     */
    protected function showImportTablesByGroupAsTree(OutputInterface $output): void
    {
        $importGroupKeys = $this->dataTransformerFactory->getRegisteredGroupKeys();
        $table = new Table($output);
        $table->setHeaders([
            'Import group',
            'Tables',
            'Priorities',
        ]);
        foreach ($importGroupKeys as $groupKey) {
            $this->dataTransformerFactory->setGroupKey($groupKey);
            $dataTransformers = $this->dataTransformerFactory->getAll();
            $mapTablesToPriority = [];
            foreach ($dataTransformers as $dataTransformer) {
                $mapTablesToPriority[$dataTransformer->getTable()] = $dataTransformer->getPriority(null);
            }
            arsort($mapTablesToPriority);
            $table->addRow([
                $groupKey,
                implode("\n", array_keys($mapTablesToPriority) ?: ['-']),
                implode("\n", $mapTablesToPriority),
            ]);
        }
        $table->render();
    }

    /**
     * Displays the configuration of import fields for a specific table in a tabular format.
     *
     * @param OutputInterface $output An OutputInterface instance used to render the table.
     * @param string $table The name of the table whose import fields configuration is displayed.
     *
     * @return void
     */
    protected function showTableImportFieldsConfiguration(OutputInterface $output, string $table): void
    {
        $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($table, true);
        $importConfiguration = $dataTransformer->getImportConfiguration();
        $importFieldMap = $importConfiguration->getImportFieldMap('import');
        if (empty($importFieldMap)) {
            $importFieldMap = $importConfiguration->getImportFieldMap('default');
        }

        $tableWidget = new Table($output);
        $tableWidget->setHeaders([
            'Source Field',
            'Target Field',
            'Normalizer',
            'Foreign Table',
            'DB Type',
            'Length',
            'Nullable',
        ]);
        foreach ($importFieldMap as $importField) {
            $field = $importField->getFieldOrFieldList();
            $tableWidget->addRow([
                is_array($field) ? implode('->', $field) : $field,
                $importField->getTargetField(),
                $importField->getDataTransformationNormalizer(),
                $importField->getReferenceTable() ?: '-',
                $importField->getDbType(),
                $importField->getLength() ?: '-',
                $importField->isNullable() ? '<fg=green>yes</>' : '<fg=yellow>no</>',
            ]);
        }
        $tableWidget->render();
    }

    protected function showGeneralTableConfiguration(SymfonyStyle $io, string $table): void
    {
        $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($table, true);
        $importConfiguration = $dataTransformer->getImportConfiguration();

        $io->definitionList(
            ['Table' => $importConfiguration->getTableName()],
            ['Import Group' => $importConfiguration->getImportGroupKey()],
            ['Source Identifier' => $importConfiguration->getSourceIdentifierField()],
            ['Integer IDs' => $importConfiguration->hasIntegerIdentifiers() ? 'yes' : 'no'],
            ['Language Field' => $importConfiguration->getLanguageField() ?: '-'],
            ['TransOrigPointer' => $importConfiguration->getTransOrigPointerField() ?: '-'],
            ['Deleted Field' => $importConfiguration->getDeletedField() ?: '-'],
            ['API Endpoint' => $importConfiguration->getApiEndpoint() ?: '-'],
            ['Import ID to UID' => $importConfiguration->isImportIdMappedToUidField() ? 'yes' : 'no'],
            ['Data Transformer' => $importConfiguration->getDataTransformerClass()],
            ['Priority' => $importConfiguration->getPriority()]
        );
    }

    protected function showCompleteFieldConfiguration(SymfonyStyle $io, string $table, string $targetField): void
    {
        $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($table, true);
        $importConfiguration = $dataTransformer->getImportConfiguration();
        $fieldConfiguration = $importConfiguration->getImportConfigurationForField($targetField, 'import');

        if (!$fieldConfiguration) {
            $fieldConfiguration = $importConfiguration->getImportConfigurationForField($targetField, 'default');
        }

        if (!$fieldConfiguration) {
            $io->error(sprintf('Field "%s" not found in table "%s" for process "import" or "default"', $targetField, $table));
            return;
        }

        $io->section('Field Configuration Model Properties');
        $io->definitionList(
            ['Target Field' => $fieldConfiguration->getTargetField()],
            ['Source Field' => $fieldConfiguration->getSourceField()],
            ['Field Path' => is_array($fieldConfiguration->getFieldOrFieldList()) ? implode('->', $fieldConfiguration->getFieldOrFieldList()) : $fieldConfiguration->getFieldOrFieldList()],
            ['Reference Table' => $fieldConfiguration->getReferenceTable() ?: '-'],
            ['Is Reference' => $fieldConfiguration->isReference() ? 'yes' : 'no'],
            ['Type' => $fieldConfiguration->getType() ?: '-'],
            ['DB Type' => $fieldConfiguration->getDbType() ?: '-'],
            ['Length' => $fieldConfiguration->getLength() ?: '-'],
            ['Collection Normalizer' => $fieldConfiguration->getDataCollectionNormalizer() ?: '-'],
            ['Transformation Normalizer' => $fieldConfiguration->getDataTransformationNormalizer() ?: '-'],
            ['Nullable' => $fieldConfiguration->isNullable() ? 'yes' : 'no'],
            ['Default' => var_export($fieldConfiguration->getDefault(), true)]
        );

        $io->section('Raw Configuration Array');
        $rawConfig = [];
        // Since $configuration is protected, we can't access it directly, but we can use get() if we know the keys.
        // However, we want to show all keys.
        // Let's check if there is a way to get the whole array.
        // Looking at ImportFieldConfigurationModel, it doesn't have a getConfiguration() method.
        // I should probably add one or use Reflection. Reflection is easier for a debug command.

        $reflection = new \ReflectionClass($fieldConfiguration);
        $property = $reflection->getProperty('configuration');
        $property->setAccessible(true);
        $config = $property->getValue($fieldConfiguration);

        foreach ($config as $key => $value) {
            $rawConfig[] = [$key => is_scalar($value) ? (string)$value : json_encode($value)];
        }
        $io->definitionList(...$rawConfig);
    }

    /**
     * Displays the configuration of the import group table in a tabular format.
     *
     * @param OutputInterface $output An OutputInterface instance used to render the table.
     * @param string $groupKey The key identifying the group for which the table configuration is displayed.
     *
     * @return void
     */
    protected function showImportGroupTableConfiguration(OutputInterface $output, string $groupKey): void
    {
        $table = new Table($output);
        $table->setHeaders([
            'Table',
            'Source ID',
            'Translatable',
            'Source fields',
            'Target fields'
        ]);
        $this->dataTransformerFactory->setGroupKey($groupKey);
        $dataTransformers = $this->dataTransformerFactory->getAll();
        foreach ($dataTransformers as $dataTransformer) {
            $importConfiguration = $dataTransformer->getImportConfiguration();
            $sourceFields = [];
            $targetFields = [];
            foreach ($importConfiguration->getImportFieldMap() as $mapEntry) {
                $field = $mapEntry->getFieldOrFieldList();
                $sourceFields[] = is_array($field) ? implode('->', $field) : $field;
                $targetFields[] = $mapEntry->getTargetField();
            }
            $table->addRow(
                [
                    '<options=bold>' . $dataTransformer->getTable() . '</>',
                    $importConfiguration->getSourceIdentifierField(),
                    $importConfiguration->hasLanguageField() ? '<fg=green>yes</>' : '<fg=yellow>no</>',
                    implode("\n", $sourceFields),
                    implode("\n", $targetFields),
                ]
            );
        }
        $table->render();
    }
}
