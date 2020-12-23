<?php

namespace XHGui;

use DateTime;
use Exception;

/**
 * Domain object for handling profile runs.
 *
 * Provides method to manipulate the data from a single profile run.
 *
 * https://github.com/tideways/php-xhprof-extension#data-format
 */
class Profile
{
    /**
     * @const Key used for methods with no parent
     */
    private const NO_PARENT = '__xhgui_top__';

    private $data;
    private $collapsed;
    private $indexed;
    private $visited;
    private $links;
    private $nodes;

    private $keys = ['ct', 'wt', 'cpu', 'mu', 'pmu'];
    private $exclusiveKeys = ['ewt', 'ecpu', 'emu', 'epmu'];
    private $functionCount;

    public function __construct(array $profile, $convert = true)
    {
        $this->data = $profile;

        // cast MongoIds to string
        if (isset($this->data['_id']) && !is_string($this->data['_id'])) {
            $this->data['_id'] = (string) $this->data['_id'];
        }

        if (!empty($profile['profile']) && $convert) {
            $this->process();
        }
    }

    /**
     * Convert the raw data into a flatter list that is easier to use.
     *
     * This removes some of the parentage detail as all calls of a given
     * method are aggregated. We are not able to maintain a full tree structure
     * in any case, as xhprof only keeps one level of detail.
     */
    private function process(): void
    {
        $result = [];
        foreach ($this->data['profile'] as $name => $values) {
            [$parent, $func] = $this->splitName($name);
            // normalize, fill all missing keys
            $values += [
                'ct' => 0,
                'wt' => 0,
                'cpu' => 0,
                'mu' => 0,
                'pmu' => 0,
            ];

            // Generate collapsed data.
            if (isset($result[$func])) {
                $result[$func] = $this->_sumKeys($result[$func], $values);
                $result[$func]['parents'][] = $parent;
            } else {
                $result[$func] = $values;
                $result[$func]['parents'] = [$parent];
            }

            // Build the indexed data.
            if ($parent === null) {
                $parent = self::NO_PARENT;
            }
            if (!isset($this->indexed[$parent])) {
                $this->indexed[$parent] = [];
            }
            $this->indexed[$parent][$func] = $values;
        }
        $this->collapsed = $result;
    }

    /**
     * Sum up the values in $this->_keys;
     *
     * @param array $a The first set of profile data
     * @param array $b the second set of profile data
     * @return array merged profile data
     */
    protected function _sumKeys($a, $b)
    {
        foreach ($this->keys as $key) {
            if (!isset($a[$key])) {
                $a[$key] = 0;
            }
            $a[$key] += $b[$key] ?? 0;
        }

        return $a;
    }

    protected function _diffKeys($a, $b, $includeSelf = true)
    {
        $keys = $this->keys;
        if ($includeSelf) {
            $keys = array_merge($keys, $this->exclusiveKeys);
        }
        foreach ($keys as $key) {
            $a[$key] -= $b[$key];
        }

        return $a;
    }

    protected function _diffPercentKeys($a, $b, $includeSelf = true)
    {
        $out = [];
        $keys = $this->keys;
        if ($includeSelf) {
            $keys = array_merge($keys, $this->exclusiveKeys);
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
        return $this->collapsed;
    }

    public function getId()
    {
        return $this->data['_id'];
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
     * @param string $key the dotted key to read
     * @return mixed|null null on failure, otherwise the stored value
     */
    public function getMeta($key = null)
    {
        $data = $this->data['meta'];
        if ($key === null) {
            return $data;
        }
        $parts = explode('.', $key);
        foreach ($parts as $key) {
            if (is_array($data) && isset($data[$key])) {
                $data = &$data[$key];
            } else {
                return null;
            }
        }

        return $data;
    }

    /**
     * Read data from the profile run.
     *
     * @param string $key the function key name to read
     * @param string $metric the metric to read
     * @return float|null
     */
    public function get($key, $metric = null)
    {
        if (!isset($this->collapsed[$key])) {
            return null;
        }
        if (empty($metric)) {
            return $this->collapsed[$key];
        }
        if (!isset($this->collapsed[$key][$metric])) {
            return null;
        }

        return $this->collapsed[$key][$metric];
    }

    /**
     * Find a function matching a watched function.
     *
     * @param string $pattern the pattern to look for
     * @return array|null an list of matching functions
     *    or null
     */
    public function getWatched($pattern)
    {
        if (isset($this->collapsed[$pattern])) {
            $data = $this->collapsed[$pattern];
            $data['function'] = $pattern;

            return [$data];
        }
        $matches = [];
        $keys = array_keys($this->collapsed);
        foreach ($keys as $func) {
            if (preg_match('`^' . $pattern . '$`', $func)) {
                $data = $this->collapsed[$func];
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
     * @param string $symbol the name of the function/method to find
     *    relatives for
     * @param string $metric the metric to compare $threshold with
     * @param float $threshold The threshold to exclude child functions at. Any
     *   function that represents less than this percentage of the current metric
     *   will be filtered out.
     * @return array List of (parent, current, children)
     */
    public function getRelatives($symbol, $metric = null, $threshold = 0)
    {
        $parents = [];

        // If the function doesn't exist, it won't have parents/children
        if (empty($this->collapsed[$symbol])) {
            return [
                [],
                [],
                [],
            ];
        }
        $current = $this->collapsed[$symbol];
        $current['function'] = $symbol;

        $parents = $this->_getParents($symbol);
        $children = $this->_getChildren($symbol, $metric, $threshold);

        return [$parents, $current, $children];
    }

    /**
     * Get the parent methods for a given symbol.
     *
     * @param string $symbol the name of the function/method to find
     *    parents for
     * @return array List of parents
     */
    protected function _getParents($symbol)
    {
        $parents = [];
        $current = $this->collapsed[$symbol];
        foreach ($current['parents'] as $parent) {
            if (isset($this->collapsed[$parent])) {
                $parents[] = ['function' => $parent] + $this->collapsed[$parent];
            }
        }

        return $parents;
    }

    /**
     * Find symbols that are the children of the given name.
     *
     * @param string $symbol the name of the function to find children of
     * @param string $metric the metric to compare $threshold with
     * @param float $threshold The threshold to exclude functions at. Any
     *   function that represents less than
     * @return array an array of child methods
     */
    protected function _getChildren($symbol, $metric = null, $threshold = 0)
    {
        $children = [];
        if (!isset($this->indexed[$symbol])) {
            return $children;
        }

        $total = 0;
        if (isset($metric)) {
            $top = $this->indexed[self::NO_PARENT];
            // Not always 'main()'
            $mainFunc = current($top);
            $total = $mainFunc[$metric];
        }

        foreach ($this->indexed[$symbol] as $name => $data) {
            if (
                $metric && $total > 0 && $threshold > 0 &&
                ($this->collapsed[$name][$metric] / $total) < $threshold
            ) {
                continue;
            }
            $children[] = $data + ['function' => $name];
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
     * @return array array of data with name = function name and
     *   value = the dimension
     */
    public function extractDimension($dimension, $limit)
    {
        $profile = $this->sort($dimension, $this->collapsed);
        $slice = array_slice($profile, 0, $limit);
        $extract = [];
        foreach ($slice as $func => $funcData) {
            $extract[] = [
                'name' => $func,
                'value' => $funcData[$dimension],
            ];
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
     * @return Profile a new instance with exclusive data set
     */
    public function calculateSelf()
    {
        // Init exclusive values
        foreach ($this->collapsed as &$data) {
            $data['ewt'] = $data['wt'];
            $data['emu'] = $data['mu'];
            $data['ecpu'] = $data['cpu'];
            $data['ect'] = $data['ct'];
            $data['epmu'] = $data['pmu'];
        }
        unset($data);

        // Go over each method and remove each childs metrics
        // from the parent.
        foreach ($this->collapsed as $name => $data) {
            $children = $this->_getChildren($name);
            foreach ($children as $child) {
                $this->collapsed[$name]['ewt'] -= $child['wt'];
                $this->collapsed[$name]['emu'] -= $child['mu'];
                $this->collapsed[$name]['ecpu'] -= $child['cpu'];
                $this->collapsed[$name]['ect'] -= $child['ct'];
                $this->collapsed[$name]['epmu'] -= $child['pmu'];
            }
        }

        return $this;
    }

    /**
     * Sort data by a dimension.
     *
     * @param string $dimension the dimension to sort by
     * @param array $data the data to sort
     * @return array the sorted data
     */
    public function sort($dimension, $data)
    {
        $sorter = static function ($a, $b) use ($dimension) {
            if ($a[$dimension] == $b[$dimension]) {
                return 0;
            }

            return $a[$dimension] > $b[$dimension] ? -1 : 1;
        };
        uasort($data, $sorter);

        return $data;
    }

    /**
     * @param array $profileData
     * @param array $filters
     *
     * @return array
     */
    public function filter($profileData, $filters = [])
    {
        foreach ($filters as $key => $item) {
            foreach ($profileData as $keyItem => $method) {
                if (fnmatch($item, $keyItem)) {
                    unset($profileData[$keyItem]);
                }
            }
        }

        return $profileData;
    }

    /**
     * Split a key name into the parent==>child format.
     *
     * @param string $name the name to split
     * @return array An array of parent, child. parent will be null if there
     *    is no parent.
     */
    public function splitName($name)
    {
        $a = explode('==>', $name);
        if (isset($a[1])) {
            return $a;
        }

        return [null, $a[0]];
    }

    /**
     * Get the total number of tracked function calls in this run.
     *
     * @return int
     */
    public function getFunctionCount()
    {
        if ($this->functionCount) {
            return $this->functionCount;
        }
        $total = 0;
        foreach ($this->collapsed as $data) {
            $total += $data['ct'];
        }
        $this->functionCount = $total;

        return $this->functionCount;
    }

    /**
     * Compare this run to another run.
     *
     * @param Profile $head The other run to compare with
     * @return array an array of comparison data
     */
    public function compare(self $head)
    {
        $this->calculateSelf();
        $head->calculateSelf();

        $keys = array_merge($this->keys, $this->exclusiveKeys);
        $emptyData = array_fill_keys($keys, 0);

        $diffPercent = [];
        $diff = [];
        foreach ($this->collapsed as $key => $baseData) {
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

        return [
            'base' => $this,
            'head' => $head,
            'diff' => $diff,
            'diffPercent' => $diffPercent,
        ];
    }

    /**
     * Get the max value for any give metric.
     *
     * @param string $metric the metric to get a max value for
     */
    protected function _maxValue($metric)
    {
        return array_reduce(
            $this->collapsed,
            static function ($result, $item) use ($metric) {
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
        $valid = array_merge($this->keys, $this->exclusiveKeys);
        if (!in_array($metric, $valid)) {
            throw new Exception("Unknown metric '$metric'. Cannot generate callgraph.");
        }
        $this->calculateSelf();

        // Non exclusive metrics are always main() because it is the root call scope.
        if (in_array($metric, $this->exclusiveKeys)) {
            $main = $this->_maxValue($metric);
        } else {
            $main = $this->collapsed['main()'][$metric];
        }

        $this->visited = $this->nodes = $this->links = [];
        $this->callgraphData(self::NO_PARENT, $main, $metric, $threshold);
        $out = [
            'metric' => $metric,
            'total' => $main,
            'nodes' => $this->nodes,
            'links' => $this->links,
        ];
        unset($this->visited, $this->nodes, $this->links);

        return $out;
    }

    private function callgraphData($parentName, $main, $metric, $threshold, $parentIndex = null): void
    {
        // Leaves don't have children, and don't have links/nodes to add.
        if (!isset($this->indexed[$parentName])) {
            return;
        }

        $children = $this->indexed[$parentName];
        foreach ($children as $childName => $metrics) {
            $metrics = $this->collapsed[$childName];
            if ($metrics[$metric] / $main <= $threshold) {
                continue;
            }
            $revisit = false;

            // Keep track of which nodes we've visited and their position
            // in the node list.
            if (!isset($this->visited[$childName])) {
                $index = count($this->nodes);
                $this->visited[$childName] = $index;

                $this->nodes[] = [
                    'name' => $childName,
                    'callCount' => $metrics['ct'],
                    'value' => $metrics[$metric],
                ];
            } else {
                $revisit = true;
                $index = $this->visited[$childName];
            }

            if ($parentIndex !== null) {
                $this->links[] = [
                    'source' => $parentName,
                    'target' => $childName,
                    'callCount' => $metrics['ct'],
                ];
            }

            // If the current function has more children,
            // walk that call subgraph.
            if (isset($this->indexed[$childName]) && !$revisit) {
                $this->callgraphData($childName, $main, $metric, $threshold, $index);
            }
        }
    }

    public function toArray()
    {
        return $this->data;
    }
}
