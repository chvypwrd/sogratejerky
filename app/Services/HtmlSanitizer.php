<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        $config->set('HTML.AllowedElements', [
            'p', 'br', 'strong', 'em', 'b', 'i',
            'h2', 'h3', 'h4',
            'ul', 'ol', 'li',
            'blockquote',
            'a',
            'hr',
        ]);

        $config->set('HTML.AllowedAttributes', [
            'a.href', 'a.title', 'a.target', 'a.rel',
        ]);

        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);

        $config->set('Attr.EnableID', false);

        $config->set('AutoFormat.RemoveEmpty', true);

        $config->set('HTML.TidyLevel', 'heavy');

        $config->set('Cache.DefinitionImpl', null);

        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return $this->purifier->purify($html);
    }

    public function isAllowed(string $element): bool
    {
        $allowed = [
            'p', 'br', 'strong', 'em', 'b', 'i',
            'h2', 'h3', 'h4',
            'ul', 'ol', 'li',
            'blockquote',
            'a',
            'hr',
        ];

        return in_array(strtolower($element), $allowed);
    }
}
