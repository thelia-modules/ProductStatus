<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProductStatus\Service;

/**
 * Keeps `<b> <strong> <i> <em> <a> <br>` in a status description and removes every
 * attribute, except the `href` of a link when it points to an http(s) or mailto URL,
 * a path or an anchor. Anything else (other tags, event handlers, `style`,
 * `javascript:` links) is dropped.
 */
final readonly class ProductStatusDescriptionSanitizer
{
    public const ALLOWED_TAGS = ['b', 'strong', 'i', 'em', 'a', 'br'];

    private const SAFE_HREF_PATTERN = '#^(?:https?://|mailto:|/|\#|\?|[^:/?\#]+(?:[/?\#]|$))#i';

    public function sanitize(string $description): string
    {
        $description = trim(strip_tags($description, self::ALLOWED_TAGS));

        if ('' === $description) {
            return '';
        }

        $document = new \DOMDocument();
        $previousErrorMode = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$description.'</div>',
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD | \LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);

        $wrapper = $document->getElementsByTagName('div')->item(0);
        if (!$wrapper instanceof \DOMElement) {
            return '';
        }

        /** @var list<\DOMElement> $elements */
        $elements = iterator_to_array($wrapper->getElementsByTagName('*'), false);
        foreach ($elements as $element) {
            $this->keepSafeAttributesOnly($element);
        }

        $sanitized = '';
        foreach ($wrapper->childNodes as $child) {
            $sanitized .= $document->saveHTML($child);
        }

        return trim($sanitized);
    }

    private function keepSafeAttributesOnly(\DOMElement $element): void
    {
        $href = 'a' === strtolower($element->tagName) ? $element->getAttribute('href') : '';

        /** @var list<\DOMAttr> $attributes */
        $attributes = iterator_to_array($element->attributes, false);
        foreach ($attributes as $attribute) {
            $element->removeAttributeNode($attribute);
        }

        if ('' !== $href && $this->isSafeHref($href)) {
            $element->setAttribute('href', $href);
        }
    }

    private function isSafeHref(string $href): bool
    {
        if (1 === preg_match('/[\x00-\x20\x7f]/', $href)) {
            return false;
        }

        // The parser has decoded the entities it knows: one still written here (an HTML5
        // name such as `&colon;`) could be decoded by a browser into a scheme separator.
        if (1 === preg_match('/&#?[a-z0-9]+;/i', $href)) {
            return false;
        }

        return 1 === preg_match(self::SAFE_HREF_PATTERN, $href);
    }
}
