<?php
/**
 * Domain object for handling profile runs.
 *
 * Provides method to manipulate the data from a single profile run.
 */
class Xhgui_Profile
{
    /**
     * @const Key used for methods with no parent
     */
    const NO_PARENT = '__xhgui_top__';

    protected $_data;
    protected $_collapsed;
    protected $_indexed;
    protected $_visited;

    protected $_keys = array('ct', 'wt', 'cpu', 'mu', 'pmu');
    protected $_exclusiveKeys = array('ewt', 'ecpu', 'emu', 'epmu');
    protected $_functionCount;

    public function __construct($profile, $convert = true)
    {
        $this->_data = $profile;
        if (!empty($profile['profile']) && $convert) {
            $this->_process();
        }
    }

    /**
     * Convert the raw data into a flatter list that is easier to use.
     *
     * This removes some of the parentage detail as all calls of a given
     * method are aggregated. We are not able to maintain a full tree structure
     * in any case, as xhprof only keeps one level of detail.
     *
     * @return void
     */
    protected function _process()
    {
        $result = array();
        foreach ($this->_data['profile'] as $name => $values) {
            list($parent, $func) = $this->splitName($name);

            // Generate collapsed data.
            if (isset($result[$func])) {
                $result[$func] = $this->_sumKeys($result[$func], $values);
                $result[$func]['parents'][] = $parent;
            } else {
                $result[$func] = $values;
                $result[$func]['parents'] = array($parent);
            }

            // Build the indexed data.
            if ($parent === null) {
                $parent = self::NO_PARENT;
            }
            if (!isset($this->_indexed[$parent])) {
                $this->_indexed[$parent] = array();
            }
            $this->_indexed[$parent][$func] = $values;
        }
        $this->_collapsed = $result;
    }

    /**
     * Sum up the values in $this->_keys;
     *
     * @param array $a The first set of profile data
     * @param array $b The second set of profile data.
     * @return array Merged profile data.
     */
    protected function _sumKeys($a, $b)
    {
        foreach ($this->_keys as $key) {
            if (!isset($a[$key])) {
                $a[$key] = 0;
            }
            $a[$key] += isset($b[$key]) ? $b[$key] : 0;
        }
        return $a;
    }

    protected function _diffKeys($a, $b, $includeSelf = true)
    {
        $keys = $this->_keys;
        if ($includeSelf) {
            $keys = array_merge($keys, $this->_exclusiveKeys);
        }
        foreach ($keys as $key) {
            $a[$key] -= $b[$key];
        }
        return $a;
    }

    protected function _diffPercentKeys($a, $b, $includeSelf = true)
    {
        $out = array();
        $keys = $this->_keys;
        if ($includeSelf) {
            $keys = array_merge($keys, $this->_exclusiveKeys);
        }
        foreach ($keys as $key) {
            if ($b[$key] != 0) {
                $out[$key] = $a[$key] / $b[$key];
            } else {
                $out[$key] = -1;
            }
        }
        return $out;
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
        return $this->_collapsed;
    }

    public function getId()
    {
        return $this->_data['_id'];
    }

    public function getDate()
    {
        $date = $this->getMeta('SERVER.REQUEST_TIME');
        if ($date) {
            return new DateTime('@' . $date);
        }
        return new DateTime('now');
    }

    /**
     * Get meta data about the profile. Read's a . split path
     * out of the meta data in a profile. For example `SERVER.REQUEST_TIME`
     *
     * @param string $key The dotted key to read.
     * @return null|mixed Null on failure, otherwise the stored value.
     */
    public function getMeta($key = null)
    {
        $data = $this->_data['meta'];
        if ($key === null) {
            return $data;
        }
        $parts = explode('.', $key);
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
    public function get($key, $metric = null)
    {
        if (!isset($this->_collapsed[$key])) {
            return null;
        }
        if (empty($metric)) {
            return $this->_collapsed[$key];
        }
        if (!isset($this->_collapsed[$key][$metric])) {
            return null;
        }
        return $this->_collapsed[$key][$metric];
    }

    /**
     * Find a function matching a watched function.
     *
     * @param string $pattern The pattern to look for.
     * @return null|array An list of matching functions
     *    or null.
     */
    public function getWatched($pattern)
    {
        if (isset($this->_collapsed[$pattern])) {
            $data = $this->_collapsed[$pattern];
            $data['function'] = $pattern;
            return array($data);
        }
        $matches = array();
        $keys = array_keys($this->_collapsed);
        foreach ($keys as $func) {
            if (preg_match('`^' . $pattern . '$`', $func)) {
                $data = $this->_collapsed[$func];
                $data['function'] = $func;
                $matches[] = $data;
            }
        }
        return $matches;
    }

    /**
     * Find the parent and children method/functions for a given
     * symbol.
     *
     * The parent/children arrays will contain all the callers + callees
     * of the symbol given. The current index will give the total
     * inclusive values for all properties.
     *
     * @param string $symbol The name of the function/method to find
     *    relatives for.
     * @param string $metric The metric to compare $threshold with.
     * @param float $threshold The threshold to exclude child functions at. Any
     *   function that represents less than this percentage of the current metric
     *   will be filtered out.
     * @return array List of (parent, current, children)
     */
    public function getRelatives($symbol, $metric = null, $threshold = 0)
    {
        $parents = array();

        // If the function doesn't exist, it won't have parents/children
        if (empty($this->_collapsed[$symbol])) {
            return array(
                array(),
                array(),
                array(),
            );
        }
        $current = $this->_collapsed[$symbol];
        $current['function'] = $symbol;

        $parents = $this->_getParents($symbol);
        $children = $this->_getChildren($symbol, $metric, $threshold);
        return array($parents, $current, $children);
    }

    /**
     * Get the parent methods for a given symbol.
     *
     * @param string $symbol The name of the function/method to find
     *    parents for.
     * @return array List of parents
     */
    protected function _getParents($symbol)
    {
        $parents = array();
        $current = $this->_collapsed[$symbol];
        foreach ($current['parents'] as $parent) {
            if (isset($this->_collapsed[$parent])) {
                $parents[] = array('function' => $parent) + $this->_collapsed[$parent];
            }
        }
        return $parents;
    }

    /**
     * Find symbols that are the children of the given name.
     *
     * @param string $symbol The name of the function to find children of.
     * @param string $metric The metric to compare $threshold with.
     * @param float $threshold The threshold to exclude functions at. Any
     *   function that represents less than
     * @return array An array of child methods.
     */
    protected function _getChildren($symbol, $metric = null, $threshold = 0)
    {
        $children = array();
        if (!isset($this->_indexed[$symbol])) {
            return $children;
        }

        $total = 0;
        if (isset($metric)) {
            $top = $this->_indexed[self::NO_PARENT];
            // Not always 'main()'
            $mainFunc = current($top);
            $total = $mainFunc[$metric];
        }

        foreach ($this->_indexed[$symbol] as $name => $data) {
            if (
                $metric && $total > 0 && $threshold > 0 &&
                ($this->_collapsed[$name][$metric] / $total) < $threshold
            ) {
                continue;
            }
            $children[] = $data + array('function' => $name);
        }
        return $children;
    }

    /**
     * Extracts a single dimension of data
     * from a profile run.
     *
     * Useful for creating bar/column graphs.
     * The profile data will be sorted by the column
     * and then the $limit records will be extracted.
     *
     * @param string $dimension The dimension to extract
     * @param int $limit Number of elements to pull
     * @return array Array of data with name = function name and
     *   value = the dimension.
     */
    public function extractDimension($dimension, $limit)
    {
        $profile = $this->sort($dimension, $this->_collapsed);
        $slice = array_slice($profile, 0, $limit);
        $extract = array();
        foreach ($slice as $func => $funcData) {
            $extract[] = array(
                'name' => $func,
                'value' => $funcData[$dimension]
            );
        }
        return $extract;
    }

    /**
     * Generate the approximate exclusive values for each metric.
     *
     * We get a==>b as the name, we need a key for a and b in the array
     * to get exclusive values for A we need to subtract the values of B (and any other children);
     * call passing in the entire profile only, should return an array of
     * functions with their regular timing, and exclusive numbers inside ['exclusive']
     *
     * Consider:
     *              /---c---d---e
     *          a -/----b---d---e
     *
     * We have c==>d and b==>d, and in both instances d invokes e, yet we will
     * have but a single d==>e result. This is a known and documented limitation of XHProf
     *
     * We have one d==>e entry, with some values, including ct=2
     * We also have c==>d and b==>d
     *
     * We should determine how many ==>d options there are, and equally
     * split the cost of d==>e across them since d==>e represents the sum total of all calls.
     *
     * Notes:
     *  Function names are not unique, but we're merging them
     *
     * @return Xhgui_Profile A new instance with exclusive data set.
     */
    public function calculateSelf()
    {
        // Init exclusive values
        foreach ($this->_collapsed as &$data) {
            $data['ewt'] = $data['wt'];
            $data['emu'] = $data['mu'];
            $data['ecpu'] = $data['cpu'];
            $data['ect'] = $data['ct'];
            $data['epmu'] = $data['pmu'];
        }
        unset($data);

        // Go over each method and remove each childs metrics
        // from the parent.
        foreach ($this->_collapsed as $name => $data) {
            $children = $this->_getChildren($name);
            foreach ($children as $child) {
                $this->_collapsed[$name]['ewt'] -= $child['wt'];
                $this->_collapsed[$name]['emu'] -= $child['mu'];
                $this->_collapsed[$name]['ecpu'] -= $child['cpu'];
                $this->_collapsed[$name]['ect'] -= $child['ct'];
                $this->_collapsed[$name]['epmu'] -= $child['pmu'];
            }
        }
        return $this;
    }

    /**
     * Sort data by a dimension.
     *
     * @param string $dimension The dimension to sort by.
     * @param array $data The data to sort.
     * @return array The sorted data.
     */
    public function sort($dimension, $data)
    {
        $sorter = function ($a, $b) use ($dimension) {
            if ($a[$dimension] == $b[$dimension]) {
                return 0;
            }
            return $a[$dimension] > $b[$dimension] ? -1 : 1;
        };
        uasort($data, $sorter);
        return $data;
    }

    /**
     * Split a key name into the parent==>child format.
     *
     * @param string $name The name to split.
     * @return array An array of parent, child. parent will be null if there
     *    is no parent.
     */
    public function splitName($name)
    {
        $a = explode("==>", $name);
        if (isset($a[1])) {
            return $a;
        }
        return array(null, $a[0]);
    }

    /**
     * Get the total number of tracked function calls in this run.
     *
     * @return int
     */
    public function getFunctionCount()
    {
        if ($this->_functionCount) {
            return $this->_functionCount;
        }
        $total = 0;
        foreach ($this->_collapsed as $data) {
            $total += $data['ct'];
        }
        $this->_functionCount = $total;
        return $this->_functionCount;
    }

    /**
     * Compare this run to another run.
     *
     * @param Xhgui_Profile $head The other run to compare with
     * @return array An array of comparison data.
     */
    public function compare(Xhgui_Profile $head)
    {
        $this->calculateSelf();
        $head->calculateSelf();

        $keys = array_merge($this->_keys, $this->_exclusiveKeys);
        $emptyData = array_fill_keys($keys, 0);

        $diffPercent = array();
        $diff = array();
        foreach ($this->_collapsed as $key => $baseData) {
            $headData = $head->get($key);
            if (!$headData) {
                $diff[$key] = $this->_diffKeys($emptyData, $baseData);
                continue;
            }
            $diff[$key] = $this->_diffKeys($headData, $baseData);

            if ($key === 'main()') {
                $diffPercent[$key] = $this->_diffPercentKeys($headData, $baseData);
            }
        }

        $diff['functionCount'] = $head->getFunctionCount() - $this->getFunctionCount();
        $diffPercent['functionCount'] = $head->getFunctionCount() / $this->getFunctionCount();

        return array(
            'base' => $this,
            'head' => $head,
            'diff' => $diff,
            'diffPercent' => $diffPercent,
        );
    }

    /**
     * Get the max value for any give metric.
     *
     * @param string $metric The metric to get a max value for.
     */
    protected function _maxValue($metric)
    {
        return array_reduce(
            $this->_collapsed,
            function ($result, $item) use ($metric) {
                if ($item[$metric] > $result) {
                    return $item[$metric];
                }
                return $result;
            },
            0
        );
    }

    /**
     * Return a structured array suitable for generating callgraph visualizations.
     *
     * Functions whose inclusive time is less than 2% of the total time will
     * be excluded from the callgraph data.
     *
     * @return array
     */
    public function getCallgraph($metric = 'wt', $threshold = 0.01)
    {
        $valid = array_merge($this->_keys, $this->_exclusiveKeys);
        if (!in_array($metric, $valid)) {
            throw new Exception("Unknown metric '$metric'. Cannot generate callgraph.");
        }
        $this->calculateSelf();

        // Non exclusive metrics are always main() because it is the root call scope.
        if (in_array($metric, $this->_exclusiveKeys)) {
            $main = $this->_maxValue($metric);
        } else {
            $main = $this->_collapsed['main()'][$metric];
        }

        $this->_visited = $this->_nodes = $this->_links = array();
        $this->_callgraphData(self::NO_PARENT, $main, $metric, $threshold);
        $out = array(
            'metric' => $metric,
            'total' => $main,
            'nodes' => $this->_nodes,
            'links' => $this->_links
        );
        unset($this->_visited, $this->_nodes, $this->_links);
        return $out;
    }

    protected function _callgraphData($parentName, $main, $metric, $threshold, $parentIndex = null)
    {
        // Leaves don't have children, and don't have links/nodes to add.
        if (!isset($this->_indexed[$parentName])) {
            return;
        }

        $children = $this->_indexed[$parentName];
        foreach ($children as $childName => $metrics) {
            $metrics = $this->_collapsed[$childName];
            if ($metrics[$metric] / $main <= $threshold) {
                continue;
            }
            $revisit = false;

            // Keep track of which nodes we've visited and their position
            // in the node list.
            if (!isset($this->_visited[$childName])) {
                $index = count($this->_nodes);
                $this->_visited[$childName] = $index;

                $this->_nodes[] = array(
                    'name' => $childName,
                    'callCount' => $metrics['ct'],
                    'value' => $metrics[$metric],
                );
            } else {
                $revisit = true;
                $index = $this->_visited[$childName];
            }

            if ($parentIndex !== null) {
                $this->_links[] = array(
                    'source' => $parentName,
                    'target' => $childName,
                    'callCount' => $metrics['ct'],
                );
            }

            // If the current function has more children,
            // walk that call subgraph.
            if (isset($this->_indexed[$childName]) && !$revisit) {
                $this->_callgraphData($childName, $main, $metric, $threshold, $index);
            }
        }
    }

    /**
     * Return a structured array suitable for generating flamegraph visualizations.
     *
     * Functions whose inclusive time is less than 1% of the total time will
     * be excluded from the callgraph data.
     *
     * @return array
     */
    public function getFlamegraph($metric = 'wt', $threshold = 0.01)
    {
        $valid = array_merge($this->_keys, $this->_exclusiveKeys);
        if (!in_array($metric, $valid)) {
            throw new Exception("Unknown metric '$metric'. Cannot generate flamegraph.");
        }
        $this->calculateSelf();

        // Non exclusive metrics are always main() because it is the root call scope.
        if (in_array($metric, $this->_exclusiveKeys)) {
            $main = $this->_maxValue($metric);
        } else {
            $main = $this->_collapsed['main()'][$metric];
        }

        $this->_visited = $this->_nodes = $this->_links = array();
        $flamegraph = $this->_flamegraphData(self::NO_PARENT, $main, $metric, $threshold);
        return array('data' => array_shift($flamegraph), 'sort' => $this->_visited);
    }

    protected function _flamegraphData($parentName, $main, $metric, $threshold, $parentIndex = null)
    {
        $result = array();
        // Leaves don't have children, and don't have links/nodes to add.
        if (!isset($this->_indexed[$parentName])) {
            return $result;
        }

        $children = $this->_indexed[$parentName];
        foreach ($children as $childName => $metrics) {
            $metrics = $this->_collapsed[$childName];
            if ($metrics[$metric] / $main <= $threshold) {
                continue;
            }
            $current = array(
                'name' => $childName,
                'value' => $metrics[$metric],
            );
            $revisit = false;

            // Keep track of which nodes we've visited and their position
            // in the node list.
            if (!isset($this->_visited[$childName])) {
                $index = count($this->_nodes);
                $this->_visited[$childName] = $index;
                $this->_nodes[] = $current;
            } else {
                $revisit = true;
                $index = $this->_visited[$childName];
            }

            // If the current function has more children,
            // walk that call subgraph.
            if (isset($this->_indexed[$childName]) && !$revisit) {
                $grandChildren = $this->_flamegraphData($childName, $main, $metric, $threshold, $index);
                if (!empty($grandChildren)) {
                    $current['children'] = $grandChildren;
                }
            }

            $result[] = $current;
        }
        return $result;
    }

    public function toArray()
    {
        return $this->_data;
    }
}
