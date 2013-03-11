<?php
/**
 * Domain object for handling profile runs.
 *
 * Provides method to manipulate the data from a single profile run.
 */
class Xhgui_Profile
{
    protected $_data;

    public function __construct($profile)
    {
        $this->_data = $profile;
    }

    /**
     * Get the profile run data.
     *
     * TODO remove this and move all the features using it into this/
     * other classes.
     *
     * @return array
     */
    public function getProfile()
    {
        return $this->_data['profile'];
    }

    public function getId()
    {
        return $this->_data['_id'];
    }

    /**
     * Get meta data about the profile. Read's a . split path
     * out of the meta data in a profile. For example `SERVER.REQUEST_TIME`
     *
     * @param string $key The dotted key to read.
     * @return null|mixed Null on failure, otherwise the stored value.
     */
    public function getMeta($key)
    {
        $parts = explode('.', $key);
        $data = $this->_data['meta'];
        foreach ($parts as $key) {
            if (is_array($data) && isset($data[$key])) {
                $data =& $data[$key];
            } else {
                return null;
            }
        }
        return $data;
    }

    /**
     * Read data from the profile run.
     *
     * @param string $key The function key name to read.
     * @param string $metric The metric to read.
     * @return null|float
     */
    public function get($key, $metric)
    {
        if (!isset($this->_data['profile'][$key][$metric])) {
            return null;
        }
        return $this->_data['profile'][$key][$metric];
    }

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
    public function getRelatives($symbol)
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
        foreach ($this->_data['profile'] as $name => $data) {
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
