<?php namespace Responsiv\Pyrolancer\Helpers;

use Html as HtmlFacade;

class Html
{
    public static function seeMore($content, $expandText = null, $length = 300)
    {
        if (!$expandText) {
            $expandText = 'See more';
        }

        return '<div data-control="minimal-content" data-show-chars="'.$length.'" data-more-text="'.$expandText.'">'.$content.'</div>';
    }

    public static function seeMoreServer($content, $expandText = null, $length = 300)
    {
        if (!$expandText) {
            $expandText = 'See more';
        }

        $short = HtmlFacade::limit($content, $length, '...');
        $long = $content;

        if (strlen($short) == strlen($long)) {
            return $long;
        }

        $jquery = "$(this).closest('[data-short]').hide().next().show()";
        $link = ' <a href="javascript:;" onclick="'.$jquery.'">'.$expandText.'</a>';

        /*
         * Inject link
         */
        $closeTag = '</';
        $pos = strrpos($short, $closeTag);
        if ($pos !== false) {
            $short = substr_replace($short, $link.$closeTag, $pos, strlen($closeTag));
        }
        else {
            $short = $short . $link;
        }

        $short = '<div data-short>'.$short.'</div>';
        $long = '<div style="display:none" data-long>'.$long.'</div>';
        return $short.$long;
    }
}
