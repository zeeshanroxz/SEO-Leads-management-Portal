<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitiser for the report editor (Phase 1 prototype).
 * Phase 2 must use a maintained sanitisation library and store files safely.
 */
class HtmlSanitizer
{
    private const TAGS = ['h1', 'h2', 'h3', 'h4', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img', 'div', 'span', 'blockquote', 'hr', 'figure', 'figcaption'];

    private const ATTRS = ['a' => ['href', 'title'], 'img' => ['src', 'alt', 'width', 'height'], 'div' => ['data-visibility'], 'td' => ['colspan', 'rowspan'], 'th' => ['colspan', 'rowspan']];

    public static function clean(?string $html): string
    {
        if (! trim((string) $html)) {
            return '';
        }
        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $doc->getElementById('root');
        if (! $root) {
            return e(strip_tags($html));
        }
        self::walk($root);
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    /** Remove blocks marked internal-only (used for the customer-facing version). */
    public static function customerFacing(string $html): string
    {
        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);
        foreach (iterator_to_array($xp->query('//*[@data-visibility="internal"]')) as $n) {
            $n->parentNode->removeChild($n);
        }
        $out = '';
        foreach ($doc->getElementById('root')->childNodes as $c) {
            $out .= $doc->saveHTML($c);
        }

        return $out;
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (! in_array($tag, self::TAGS, true)) {
                    $node->removeChild($child);

                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    if (! in_array($name, self::ATTRS[$tag] ?? [], true)) {
                        $child->removeAttribute($attr->name);

                        continue;
                    }
                    $val = trim($attr->value);
                    if (in_array($name, ['href'], true) && ! preg_match('#^(https?://|mailto:|/|\#)#i', $val)) {
                        $child->removeAttribute($attr->name);
                    }
                    if ($name === 'src' && ! preg_match('#^(https://|data:image/(png|jpe?g|gif|svg\+xml|webp)[;,])#i', $val)) {
                        $child->removeAttribute($attr->name);
                    }
                    if ($name === 'data-visibility' && $val !== 'internal') {
                        $child->removeAttribute($attr->name);
                    }
                }
                self::walk($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }
}
