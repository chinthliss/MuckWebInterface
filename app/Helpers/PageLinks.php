<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\HtmlString;

/**
 * Global helper so that it can be called easily via views.
 */
class PageLinks
{
    /**
     * Links expected as a link of the following:
     * [title, description, url]
     * @param array $links
     * @return HtmlString
     * @throws Exception
     */
    public static function render(array $links): HtmlString
    {
        $renderedItems = [];
        foreach ($links as $link) {
            if (!isset($link['title']) || !isset($link['description']) || !isset($link['url']))
                throw new Exception("Links must have title, description and url set.");
            $title = $link['title'];
            $description = $link['description'];
            $url = $link['url'];
            $renderedItems[] = <<<BLOCK
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">$title</h5>
                        <div class="card-text">$description</div>
                        <a href="$url" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            BLOCK;
        }
        return new HtmlString('<div class="row g-2">' . join('', $renderedItems) . '</div>');
    }
}
