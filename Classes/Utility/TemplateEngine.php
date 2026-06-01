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

namespace BrainAppeal\CampusEventsConnector\Utility;

use BrainAppeal\CampusEventsConnector\Domain\Model\ConvertConfiguration;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;

class TemplateEngine
{
    /**
     * @var ViewInterface[]
     */
    private array $templateRendererCache = [];

    public function __construct(protected ViewFactoryInterface $viewFactory, private readonly ResourceFactory $resourceFactory) {}

    /**
     * @param ConvertConfiguration $configuration
     * @return string[]
     */
    protected function getTemplateRootPaths(ConvertConfiguration $configuration): array
    {
        return [
            0 => $configuration->getTemplatePath(),
        ];
    }

    /**
     * @param string $path
     * @return string
     */
    private function resolvePath(string $path): string
    {
        if (preg_match('/^\d+:/', $path)) {
            $resourceFactory = $this->resourceFactory;
            $folder = $resourceFactory->getFolderObjectFromCombinedIdentifier($path);
            if (!($folder instanceof InaccessibleFolder)) {
                $path = (string)$folder->getPublicUrl();
            }
        }

        return GeneralUtility::getFileAbsFileName($path);
    }

    /**
     * @param ConvertConfiguration $configuration
     * @return string[]
     */
    private function getResolvedTemplateRootPaths(ConvertConfiguration $configuration): array
    {
        $templateRootPaths = $this->getTemplateRootPaths($configuration);
        foreach ($templateRootPaths as &$templateRootPath) {
            $templateRootPath = $this->resolvePath($templateRootPath);
        }

        return $templateRootPaths;
    }

    /**
     * @param ConvertConfiguration $configuration
     * @param string $templateName
     * @return ViewInterface
     */
    private function getTemplateRenderer(ConvertConfiguration $configuration, string $templateName): ViewInterface
    {
        if (!isset($this->templateRendererCache[$templateName])) {
            $templateRootPaths = $this->getResolvedTemplateRootPaths($configuration);

            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: $templateRootPaths,
                format: 'html'
            );
            $view = $this->viewFactory->create($viewFactoryData);

            $this->templateRendererCache[$templateName] = $view;
        }

        return $this->templateRendererCache[$templateName];
    }

    /**
     * @param ConvertConfiguration $configuration
     * @param string $templateName
     * @param array $values
     * @param bool $stripLineBreaks
     * @return string
     */
    public function getFromTemplate(ConvertConfiguration $configuration, string $templateName, array $values, bool $stripLineBreaks = true): string
    {
        $view = $this->getTemplateRenderer($configuration, $templateName);
        $view->assignMultiple($values);
        $html = $view->render($templateName);
        if ($stripLineBreaks) {
            $html = str_replace(["\r", "\n"], '', $html);
        }
        return $html;
    }
}
