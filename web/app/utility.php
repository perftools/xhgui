<?php


// Take in huge flat array, turn into heirarchy
// We get a==>b as the name, we need a key for a and b in the array
// to get exclusive values for A we need to subtract the values of B (and any other children);
//call passing in the entire profile only, should return an array of functions with their regular timing, and exclusive numbers inside ['exclusive']
/*
 *
 *
 * Consider:
 *               ---c---d---e
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
 */
function exclusive($run)
{
    $final = array();
    //Create a list of each function
    foreach((array)$run as $name => $data)
    {
        $name = splitName($name);
        $parent = $name[0];
        $child = $name[1];
        //Init exclusive values 
        $data['ewt'] = $data['wt'];
        $data['emu'] = $data['mu'];
        $data['ecpu'] = $data['cpu'];
        $data['ect'] = $data['ct'];
        $data['epmu'] = $data['pmu'];
        
        
        //Set parent
        $data['parent'] = $parent;
        
        
        if(!isset($final[$child]))
        {
            //Save all this data as the child function, this is wrong (since we'll clobber something if the same function is called from two places)
            $final[$child] = $data;    
        }else {
            $final[$child] = addFunctions($final[$child], $data);
        }
        
    }
    
    //Delete from parent its children, this is wrong
    foreach($final as $child => $data)
    {
        //echo "I am $child, My parent is: {$data['parent']}\n";
        //var_dump($data);
        if (isset($final[$data['parent']]))
        {
            $final[$data['parent']]['ewt'] -= $data['wt'];
            $final[$data['parent']]['emu'] -= $data['mu'];
            $final[$data['parent']]['ecpu'] -= $data['cpu'];
            $final[$data['parent']]['ect'] -= $data['ct'];
            $final[$data['parent']]['epmu'] -= $data['pmu'];
        }
        
    }
    return $final;
}

function addFunctions($a, $b)
{
    $c = array();
    foreach($a as $k => $v)
    {
        $c[$k] = $v + $b[$k];
    }
    return $c;
}

function splitName($name)
{
    //we have a==>b or just a
    $a = explode("==>", $name);
    if (isset($a[1]))
    {
        return $a;
    }

    return array(null, $a[0]);
}

/**
 * Extracts a single dimension of data
 * from a profile run.
 *
 * Useful for creating bar/column graphs.
 * The profile data will be sorted by the column
 * and then the $limit records will be extracted.
 *
 * @param array $profile Profile data.
 * @param string $dimension The dimension to extract
 * @param int $limit Number of elements to pull
 * @return array Array of data with name = function name and 
 *   value = the dimension.
 */
function extractDimension($profile, $dimension, $limit)
{
    uasort($profile, build_sorter($dimension));
    $slice = array_slice($profile, 0, $limit);
    $extract = array();
    foreach ($slice as $func => $funcData)
    {
        $extract[] = array(
            'name' => $func,
            'value' => $funcData[$dimension]
        );
    }
    return $extract;
}


/**
 * Creates a simplified URL given a standard URL.
 * Does the following transformations:
 *
 * - Remove numeric values after =.
 *
 * @param string $url
 * @return string
 */
function simpleUrl($url)
{
    $url = preg_replace('/\=\d+/', '', $url);
    // TODO Add hooks for customizing this.
    return $url;
}
