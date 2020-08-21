<?php

use Slim\Http\Request;
use Slim\Slim;

class Xhgui_Controller_Import extends Xhgui_Controller
{
    /**
     * @var Xhgui_Saver_Interface
     */
    private $saver;

    /** @var string */
    private $token;

    public function __construct(Slim $app, Xhgui_Saver_Interface $saver, $token)
    {
        parent::__construct($app);
        $this->saver = $saver;
        $this->token = $token;
    }

    public function import()
    {
        $request = $this->app->request();
        $response = $this->app->response();

        try {
            $this->runImport($request);
            $result = ['ok' => true, 'size' => $request->getContentLength()];
        } catch (InvalidArgumentException $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            $response->setStatus(401);
        } catch (Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            $response->setStatus(500);
        }

        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($result));
    }

    private function runImport(Request $request)
    {
        if ($this->token) {
            if ($this->token !== $request->get('token')) {
                throw new InvalidArgumentException('Token validation failed');
            }
        }

        $data = json_decode($request->getBody(), true);
        $this->saver->save($data);
    }
}
