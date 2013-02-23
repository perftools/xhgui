<?php

class Xhgui_Profile
{
    /**
     * Find the parent and children method/functions for a given
     * symbol
     *
     * @param array $profile Array of profile data.
     * @param string $symbol The name of the function/method to find
     *    relatives for.
     * @return array List of (parent, current, children)
     */
    public static function getRelatives($profile, $symbol)
    {
        $currentMethod = $parentMethod = $children = array();
        foreach ($profile as $name => $data) {
            list($parent, $child) = splitName($name);
            if ($parent === $symbol) {
                $children[] = $data + array('function' => $child);
            } elseif ($child === $symbol) {
                $parentMethod = $data + array('function' => $parent);
                $currentMethod = $data + array('function' => $child);
            }
        }
        return array($parentMethod, $currentMethod, $children);
    }
}
