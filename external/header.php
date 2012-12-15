<?php

/* Things you may want to tweak in here:
 *  - xhprof_enable() uses a few constants.
 *  - The values passed to rand() determine the the odds of any particular run being profiled.
 *  - The MongoDB collection and such.
 *
 * I use unsafe writes by default, let's not slow down requests any more than I need to. As a result you will
 * indubidubly want to ensure that writes are actually working. 
 */

 
/* xhprof_enable()
 * See: http://php.net/manual/en/xhprof.constants.php
 *
 * 
 * XHPROF_FLAGS_NO_BUILTINS
 *  Omit built in functions from return
 *  This can be useful to simplify the output, but there's some value in seeing that you've called strpos() 2000 times
 *  
 * XHPROF_FLAGS_CPU
 *  Include CPU profiling information in output
 *  
 * XHPROF_FLAGS_MEMORY (integer)
 *  Include Memory profiling information in output
 *
 *
 * Use bitwise operators to combine, so XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY to profile CPU and Memory
 *
 */

//Obtain the answer to life, the universe, and your application one time out of a hundred 
if (rand(0, 100) === 42) {
    xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
    register_shutdown_function('recordXHProfData');
}





function recordXHProfData()
{
    //ignore_user_abort(true) allows your PHP script to continue executing, even if the user has terminated their request. 
    //flush() asks PHP to send any data remaining in the output buffers. This is normally done when the script completes, but
    //  since we're delaying that a bit by dealing with the xhprof stuff, we'll do it now to avoid making the user wait. 
    ignore_user_abort(true);
    flush();
    
    
    $data['profile'] = xhprof_disable();
    $data['meta'] = array(
        'url' => $_SERVER['REQUEST_URI'],
        'SERVER' => $_SERVER,
        'get' => $_GET,
        'env' => $_ENV,
        'simple_url' => simpleUrl($_SERVER['REQUEST_URI']),
    );
    
    $m = new Mongo();
    $db = $m->xhprof;
    $collection = $db->results;
    
    //We're fine with unsafe writes
    $collection->insert($data, array('w' => 0));
}


