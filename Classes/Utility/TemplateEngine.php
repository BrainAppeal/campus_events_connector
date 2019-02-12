<?php
/**
 * Mindbase 3
 *
 * PHP version 5.6
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2018 Brain Appeal GmbH (www.brain-appeal.com)
 * @license
 * @link      http://www.brain-appeal.com/
 * @since     2018-07-16
 */

namespace BrainAppeal\CampusEventsConnector\Utility;



class TemplateEngine
{

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView[]
     */
    private $templateRendererCache;

    public function __construct()
    {
        $this->templateRendererCache = [];
    }


    /**
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ConvertConfiguration $configuration
     * @param string $templateName
     * @return string[]
     */
    protected function getTemplateRootPaths($configuration, /** @noinspection PhpUnusedParameterInspection */ $templateName)
    {
        return [
            0 => $configuration->getTemplatePath(),
        ];
    }

    /**
     * @param string $path
     * @return string
     */
    private function resolvePath($path)
    {
        if (preg_match('/^\d+:/', $path)) {
            $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
            $path = $resourceFactory->getFolderObjectFromCombinedIdentifier($path)->getPublicUrl();
        }

        return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path);
    }

    /**
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ConvertConfiguration $configuration
     * @param string $templateName
     * @return string[]
     */
    private function getResolvedTemplateRootPaths($configuration, $templateName)
    {
        $templateRootPaths = $this->getTemplateRootPaths($configuration, $templateName);
        foreach ($templateRootPaths as &$templateRootPath) {
            $templateRootPath = $this->resolvePath($templateRootPath);
        }

        return $templateRootPaths;
    }

    /**
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ConvertConfiguration $configuration
     * @param string $templateName
     * @return \TYPO3\CMS\Fluid\View\StandaloneView
     */
    private function getTemplateRenderer($configuration, $templateName)
    {
        if (!isset($this->templateRendererCache[$templateName])) {
            $templateRootPaths = $this->getResolvedTemplateRootPaths($configuration, $templateName);

            /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
            $view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
            $view->setTemplateRootPaths($templateRootPaths);
            $view->setTemplate($templateName . '.html');

            $this->templateRendererCache[$templateName] = $view;
        }

        return $this->templateRendererCache[$templateName];
    }

    /**
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ConvertConfiguration $configuration
     * @param string $templateName
     * @param array $values
     * @param bool $stripLineBreaks
     * @return mixed
     */
    public function getFromTemplate($configuration, $templateName, $values, $stripLineBreaks = true)
    {
        $templateRenderer = $this->getTemplateRenderer($configuration, $templateName);
        $templateRenderer->assignMultiple($values);
        $html = $templateRenderer->render();
        if ($stripLineBreaks) {
            $html = preg_replace( "/\r|\n/", "", $html);
        }
        return $html;
    }
}