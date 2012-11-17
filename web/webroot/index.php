<?php
xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

$m = new MongoClient();
$db = $m->xhprof;
$collection = $db->results;


function stuff($a)
{
    for ($i = 0; $i < 1000; $i++)
    {
        $b = strlen($a);
    }
}
stuff("asdasdsda asdasijd aisdjiasdjio jiasdjio asd");

//Let's get results from the database
$res = $collection->find();
echo <<<TABLE
<table>
    <thead>
        <tr>
            <th>URL</th><th>Time</th><th>wt</th><th>cpu</th><th>mu</th><th>pmu</th>
        </tr>
    </thead>
    <tbody>
TABLE;
foreach($res as $result)
{
    $id = (string) $result['_id'];
    echo <<<ROW
    <tr>
        <td><a href="/run.php?id={$id}">{$result['meta']['url']}</a></td><td>{$result['meta']['SERVER']['REQUEST_TIME']}</td><td>{$result['profile']['main()']['wt']}</td><td>{$result['profile']['main()']['cpu']}</td><td>{$result['profile']['main()']['mu']}</td><td>{$result['profile']['main()']['pmu']}</td>
    </tr>
    
ROW;
}
echo <<<TABLECLOSE
    </tbody>
</table>
<pre>
TABLECLOSE;
var_dump($result);


//Store results
$profile = xhprof_disable();

function _xhGetMeta()
{
    $meta = array(
        'url' => $_SERVER['REQUEST_URI'],
        'SERVER' => $_SERVER,
        'get' => $_GET,
        'env' => $_ENV,
    );
    return $meta;
}
$data['meta'] = _xhGetMeta();
$data['profile'] = $profile;

$collection->insert($data);
$m = new MongoClient();
$db = $m->xhprof;
$collection = $db->results;