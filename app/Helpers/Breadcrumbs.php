<?php

namespace App\Helpers;

use Illuminate\Support\HtmlString;

/**
 * Global helper so that it can be called easily via views.
 */
class Breadcrumbs
{
    /**
     * Breadcrumbs expected in the format [{route, label}..]
     * @param array $breadcrumbs
     * @return HtmlString
     */
    public static function render(array $breadcrumbs): HtmlString
    {
        $listItems = [];
        foreach ($breadcrumbs as $index => $item) {
            if (isset($item['route']))
                $innerContent = '<a href="' . route($item['route']) . '">' . $item['label'] . '</a>';
            else
                $innerContent = $item['label'];
            $listItems[] = '<li class="breadcrumb-item active"' .
                ($index == count($breadcrumbs) - 1 ? ' aria-current="page"' : '') .
                '>' . $innerContent . '</li>';
        }
        return new HtmlString('<nav aria-label="breadcrumb"><ol class="breadcrumb my-1">' . join('', $listItems) . '</ol></nav>');
    }
}
