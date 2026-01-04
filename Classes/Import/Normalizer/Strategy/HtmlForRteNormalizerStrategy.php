<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Strategy for normalizing HTML values.
 */
readonly class HtmlForRteNormalizerStrategy extends AbstractNormalizerStrategy
{

    public function normalize(mixed $value): ?string
    {
        if ($value === null && $this->isNullable) {
            return null;
        }
        return $this->formatHtmlForRTE(is_string($value) ? $value : (string)$value);
    }

    /**
     * Remove line breaks after end tags in HTML to prevent RTE from adding unnecessary empty lines
     * @param ?string $html
     * @return string
     */
    protected function formatHtmlForRTE(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        $replaceHtmlWith = [
            '<br>' => ['<br />', '<br/>'],
            'ä' => '&auml;',
            'ö' => '&ouml;',
            'ü' => '&uuml;',
            'Ä' => '&Auml;',
            'Ö' => '&Ouml;',
            'Ü' => '&Uuml;',
            'ß' => '&szlig;',
        ];
        $cleanedHtml = preg_replace("/<img[^>]+>/i", '', $html);
        if (mb_strlen((string)$cleanedHtml) > 65535) {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $append = '...';
            $cleanedHtml = $contentObject->cropHTML($cleanedHtml, 65530 . '|' . $append . '|1');
        }
        foreach ($replaceHtmlWith as $replaceWith => $searchFor) {
            $cleanedHtml = str_replace($searchFor, $replaceWith, $cleanedHtml);
        }
        $cleanedHtmlRte = $cleanedHtml;
        $removeLineBreaksBeforeAndAfterTags = ['br', 'p', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'h5', 'div', 'table'];
        try {
            foreach ($removeLineBreaksBeforeAndAfterTags as $tag) {
                $tagStart = '<' . $tag . '>';
                $tagEnd = '</' . $tag . '>';
                if (stripos((string)$cleanedHtmlRte, $tagStart) !== false) {
                    $cleanedHtmlRte = preg_replace('/\s*(<' . $tag . '[^>]*>)\s*/i', '$1', (string)$cleanedHtmlRte);
                }
                if (stripos((string)$cleanedHtmlRte, $tagEnd) !== false) {
                    $cleanedHtmlRte = preg_replace('/\s*(<\/' . $tag . '>)\s*/i', '$1', (string)$cleanedHtmlRte);
                }
            }
        } catch (\Exception) {
            return $cleanedHtml;
        }
        return $cleanedHtmlRte;
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'html_for_rte';
    }
}
