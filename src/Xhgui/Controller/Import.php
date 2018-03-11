<?php

use Slim\Slim;

class Xhgui_Controller_Import extends Xhgui_Controller
{
    /**
     * @var Xhgui_Saver_Mongo
     */
    private $saver;

    public function __construct(Slim $app, Xhgui_Saver_Mongo $saver)
    {
        $this->app = $app;
        $this->saver = $saver;
    }

    public function import()
    {
        $request = $this->app->request();
        $response = $this->app->response();

        $data = json_decode($request->getBody(), true);
        $this->saver->save($data);

        $response['Content-Type'] = 'application/json';
        $response->body(json_encode(['ok' => true]));
    }
}
