<?php


// Take in huge flat array, turn into heirarchy
// We get a==>b as the name, we need a key for a and b in the array
// to get exclusive values for A we need to subtract the values of B (and any other children);
//call passing in the entire profile only, should return an array of functions with their regular timing, and exclusive numbers inside ['exclusive']
function exclusive($run)
{
    $final = array();
    //Create a list of each function
    foreach($run as $name => $data)
    {
        
        $name = splitName($name);
        $parent = $name[0];
        $child = $name[1];
        $data['exclusive'] = $data;
        $data['parent'] = $parent;
        $final[$child] = $data;
    }
    
    //Delete from parent its children, this is wrong
    foreach($final as $child => $data)
    {
        echo "I am $child, My parent is: {$data['parent']}\n";
        //var_dump($data);
        if (isset($final[$data['parent']]))
        {
            $final[$data['parent']]['exclusive']['wt'] -= $data['wt'];
            $final[$data['parent']]['exclusive']['mu'] -= $data['mu'];
            $final[$data['parent']]['exclusive']['cpu'] -= $data['cpu'];
            $final[$data['parent']]['exclusive']['ct'] -= $data['ct'];
            $final[$data['parent']]['exclusive']['pmu'] -= $data['pmu'];
        }
        
    }
    return $final;
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