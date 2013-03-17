<?php

class Xhgui_Util
{
    /**
     * Creates a simplified URL given a standard URL.
     * Does the following transformations:
     *
     * - Remove numeric values after =.
     *
     * @param string $url
     * @return string
     */
    public static function simpleUrl($url)
    {
        $url = preg_replace('/\=\d+/', '', $url);
        // TODO Add hooks for customizing this.
        return $url;
    }
}
