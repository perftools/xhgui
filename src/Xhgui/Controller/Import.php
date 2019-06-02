<?php

use Slim\Slim;

class Xhgui_Controller_Import extends Xhgui_Controller
{
    /**
     * @var Xhgui_Saver_Interface
     */
    private $profiles;

    public function __construct(Slim $app, Xhgui_Profiles $profiles)
    {
        parent::__construct($app);
        $this->profiles = $profiles;
    }

    public function import()
    {
        $request = $this->app->request();
        $response = $this->app->response();

        $data = json_decode($request->getBody(), true);
        $this->profiles->save($data);

        $response['Content-Type'] = 'application/json';
        $response->body(json_encode(['ok' => true]));
    }
}
