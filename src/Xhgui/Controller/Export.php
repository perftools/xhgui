<?php

use Slim\Slim;
use Tideways\Xhprof\CachegrindConverter;

class Xhgui_Controller_Export extends Xhgui_Controller
{
    /** @var CachegrindConverter */
    private $converter;

    /** @var Xhgui_Searcher_Interface */
    private $searcher;

    public function __construct(Slim $app, Xhgui_Searcher_Interface $searcher)
    {
        parent::__construct($app);
        $this->searcher = $searcher;
        $this->converter = new CachegrindConverter();
    }

    public function cachegrind()
    {
        $request = $this->app->request();
        $id = $request->get('id');

        $profile = $this->searcher->get($id);
        $output = $this->converter->convertToCachegrind($profile->toArray()['profile']);

        $response = $this->app->response();
        $response['Content-Type'] = 'application/octet-stream';
        $response['Cache-Control'] = 'public, max-age=60, must-revalidate';
        $response['Content-Disposition'] = sprintf('attachment; filename=cachegrind-%s.out', $id);
        $response->body($output);
    }
}
