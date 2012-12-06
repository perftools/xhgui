<?php
require dirname(__DIR__) . '/app/bootstrap.php';

xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

$m = new Mongo();
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

$template = load_template('runs/list.twig');
echo $template->render(array(
    'runs' => $res
));

foreach($res as $result)
{
    $id = (string) $result['_id'];
    echo <<<ROW
    
ROW;
}
echo <<<TABLECLOSE
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
$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;
