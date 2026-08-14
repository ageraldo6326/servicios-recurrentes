<?php

declare(strict_types=1);

namespace App\Services\Notebooks;

use DOMDocument;
use DOMElement;
use DOMNode;

final class NoteContentSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'h1', 'h2', 'h3', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'ul', 'ol', 'li', 'blockquote', 'a', 'pre', 'code', 'hr', 'img', 'input'];

    public function sanitize(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $document->documentElement;
        $this->cleanChildren($root);
        $safeHtml = '';
        foreach ($root->childNodes as $child) {
            $safeHtml .= $document->saveHTML($child);
        }

        return ['html' => $safeHtml, 'text' => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($safeHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '')];
    }

    private function cleanChildren(DOMNode $node): void
    {
        for ($index = $node->childNodes->length - 1; $index >= 0; $index--) {
            $child = $node->childNodes->item($index);
            if (! $child instanceof DOMElement) {
                continue;
            }
            if (! in_array(strtolower($child->tagName), self::ALLOWED_TAGS, true)) {
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }
            $this->cleanAttributes($child);
            $this->cleanChildren($child);
        }
    }

    private function cleanAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $allowed = match ($tag) {
            'a' => ['href'], 'img' => ['src', 'alt'], 'input' => ['type', 'checked', 'disabled'], default => [],
        };
        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);
            if ($attribute !== null && ! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttributeNode($attribute);
            }
        }
        if ($tag === 'a' && ! $this->safeLink((string) $element->getAttribute('href'))) {
            $element->removeAttribute('href');
        }
        if ($tag === 'img' && ! $this->safeImage((string) $element->getAttribute('src'))) {
            $element->parentNode?->removeChild($element);

            return;
        }
        if ($tag === 'input' && strtolower((string) $element->getAttribute('type')) !== 'checkbox') {
            $element->parentNode?->removeChild($element);
        }
    }

    private function safeLink(string $url): bool
    {
        return preg_match('#^(https?://|mailto:|tel:)#i', trim($url)) === 1;
    }

    private function safeImage(string $url): bool
    {
        return str_starts_with(trim($url), '/notebooks/attachments/');
    }
}
