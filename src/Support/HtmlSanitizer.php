<?php

namespace Laravilt\Tables\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Small DOMDocument-based HTML sanitizer used for columns that render raw HTML (e.g. TextColumn::html()).
 *
 * Removes script-capable elements, event handler attributes and dangerous URL schemes so the
 * frontend (Vue v-html / React dangerouslySetInnerHTML) only ever receives safe markup.
 */
class HtmlSanitizer
{
    /**
     * Elements removed together with their content.
     *
     * @var array<int, string>
     */
    protected const REMOVED_ELEMENTS = [
        'script', 'style', 'iframe', 'object', 'embed', 'applet', 'frame', 'frameset',
        'base', 'link', 'meta', 'noscript', 'template', 'form',
    ];

    /**
     * Attributes that may carry a URL and must not use a script-capable scheme.
     *
     * @var array<int, string>
     */
    protected const URL_ATTRIBUTES = [
        'href', 'src', 'srcset', 'action', 'formaction', 'xlink:href', 'poster',
        'background', 'data', 'cite', 'lowsrc', 'dynsrc', 'longdesc', 'ping',
    ];

    /**
     * URL schemes that are never allowed.
     *
     * @var array<int, string>
     */
    protected const BLOCKED_SCHEMES = ['javascript', 'vbscript', 'data'];

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return (string) $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        // The XML encoding hint makes libxml parse the fragment as UTF-8 instead of ISO-8859-1
        $document->loadHTML(
            '<?xml encoding="UTF-8"?><div id="__laravilt_sanitizer_root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('__laravilt_sanitizer_root');

        if (! $root) {
            // Could not parse: fall back to fully escaped text
            return e($html);
        }

        static::cleanNode($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    protected static function cleanNode(DOMNode $node): void
    {
        // Iterate over a copy: removing nodes while iterating a live DOMNodeList skips siblings
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->nodeName), static::REMOVED_ELEMENTS, true)) {
                $node->removeChild($child);

                continue;
            }

            static::cleanAttributes($child);
            static::cleanNode($child);
        }
    }

    protected static function cleanAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = $attribute->nodeValue ?? '';

            $remove = str_starts_with($name, 'on')
                || (in_array($name, static::URL_ATTRIBUTES, true)
                    && ! static::isAllowedImageDataUrl($element, $name, $value)
                    && static::hasBlockedScheme($value))
                || ($name === 'style' && static::hasDangerousStyle($value));

            if ($remove) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    /**
     * Inline base64 raster images are allowed in <img src> only: raster formats can't execute script.
     * SVG (can embed script), other media types, and data: URLs on any other element/attribute stay blocked.
     */
    protected static function isAllowedImageDataUrl(DOMElement $element, string $attribute, string $value): bool
    {
        return strtolower($element->nodeName) === 'img'
            && $attribute === 'src'
            && preg_match('#^\s*data:image/(png|jpe?g|gif|webp|avif);base64,[a-z0-9+/=\s]*$#i', $value) === 1;
    }

    protected static function hasBlockedScheme(string $value): bool
    {
        // Browsers ignore whitespace/control characters inside the scheme ("java\tscript:")
        $normalized = strtolower(preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        foreach (static::BLOCKED_SCHEMES as $scheme) {
            // srcset/ping can hold several URLs separated by commas or spaces
            if (preg_match('/(^|[,\s])'.$scheme.':/', $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    protected static function hasDangerousStyle(string $value): bool
    {
        $normalized = strtolower(preg_replace('/[\x00-\x20\x7F\\\\]+/', '', $value) ?? '');

        return str_contains($normalized, 'expression(')
            || str_contains($normalized, 'javascript:')
            || str_contains($normalized, 'vbscript:')
            || str_contains($normalized, 'url(data:');
    }
}
