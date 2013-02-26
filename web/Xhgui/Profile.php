<?php

class Xhgui_Profile
{
    /**
     * Find the parent and children method/functions for a given
     * symbol.
     *
     * The parent/children arrays will contain all the callers + callees
     * of the symbol given. The current index will give the total
     * inclusive values for all properties.
     *
     * @param array $profile Array of profile data.
     * @param string $symbol The name of the function/method to find
     *    relatives for.
     * @return array List of (parent, current, children)
     */
    public static function getRelatives($profile, $symbol)
    {
        $parents = $children = array();
        $current = array(
            'function' => $symbol,
            'ct' => 0,
            'wt' => 0,
            'cpu' => 0,
            'mu' => 0,
            'pmu' => 0,
        );
        foreach ($profile as $name => $data) {
            list($parent, $child) = splitName($name);
            if ($parent === $symbol) {
                $children[] = $data + array('function' => $child);
            } elseif ($child === $symbol) {
                $parents[] = $data + array('function' => $parent);
                foreach ($data as $k => $v) {
                    $current[$k] += $v;
                }
            }
        }
        return array($parents, $current, $children);
    }
}
