<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class WishlistExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('wishlist', [$this, 'format'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Wishlists are plain text: escape everything, make http(s) links clickable, keep line breaks.
     */
    public function format(?string $text): string
    {
        $html = htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = preg_replace(
            '~https?://[^\s<]+[^\s<.,;:!?)\]]~i', // don't swallow trailing punctuation
            '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>',
            $html
        );

        return nl2br($html, false);
    }
}
