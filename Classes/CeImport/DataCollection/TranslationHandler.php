<?php

declare(strict_types=1);

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2025 Brain Appeal GmbH
 *
 * @copyright 2025 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\CeImport\DataCollection;

use BrainAppeal\CampusEventsConnector\Utility\TCAUtility;
use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TranslationHandler implements SingletonInterface
{
    protected string $table = '';

    /**
     * Finds translation overlays by given page Id.
     *
     * @return array{array{
     *    'uid': int,
     *    'pid': int,
     *    'l10n_parent': int,
     *    'sys_language_uid': int,
     * }}
     *
     * @throws DBALException
     */
    protected function findTranslationOverlaysByPageId(int $pageId): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'pid', 'l10n_parent', 'sys_language_uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
                . BackendUtility::BEenableFields('pages'),
            )->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Returns the translation overlays for the given page and the configured translation languages.
     * This ensures that translations are only loaded if the target page is translated to the configured languages.
     *
     * @param int $pageId
     * @return array<int, array{language_code: string, pid: int, l10n_parent: int, sys_language_uid: int, language_aspect: LanguageAspect, uid: int}>
     * @throws DBALException
     */
    public function getPageLanguageOverlays(int $pageId): array
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
        $additionalLanguageCodes = GeneralUtility::trimExplode(',', (string)($extConf['additional_language_codes'] ?? null), true);
        if (empty($additionalLanguageCodes)) {
            return [];
        }
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            $site = $siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            return [];
        }
        $mapLanguageToId = [];
        foreach ($site->getAllLanguages() as $language) {
            $languageCode = $language->getLocale()->getLanguageCode();
            if ($languageCode !== 'default' && $languageCode !== 'de' && in_array($languageCode, $additionalLanguageCodes)) {
                $mapLanguageToId[$language->getLanguageId()] = $language;
            }
        }
        $translationOverlays = $this->findTranslationOverlaysByPageId($pageId);
        $pageLanguageOverlays = [];
        foreach ($translationOverlays as $translationOverlay) {
            $languageId = (int) $translationOverlay['sys_language_uid'];
            if (isset($mapLanguageToId[$languageId])) {
                $pageLanguageOverlays[$languageId] = $translationOverlay;
                $language = $mapLanguageToId[$languageId];
                $pageLanguageOverlays[$languageId]['language_code'] = $language->getLocale()->getLanguageCode();
                $pageLanguageOverlays[$languageId]['language_aspect'] = LanguageAspectFactory::createFromSiteLanguage($language);
            }
        }
        return $pageLanguageOverlays;
    }
}
