<?php

namespace XHGui\Controller;

use Exception;
use InvalidArgumentException;
use Slim\App;
use XHGui\AbstractController;
use XHGui\RequestProxy as Request;
use XHGui\Saver\SaverInterface;

class ImportController extends AbstractController
{
    /**
     * @var SaverInterface
     */
    private $saver;

    /** @var string */
    private $token;

    public function __construct(App $app, SaverInterface $saver, $token)
    {
        parent::__construct($app);
        $this->saver = $saver;
        $this->token = $token;
    }

    public function import(Request $request)
    {
        $status = 200;
        try {
            $id = $this->runImport($request);
            $result = ['ok' => true, 'id' => $id, 'size' => $request->getContentLength()];
        } catch (InvalidArgumentException $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            $status = 401;
        } catch (Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            $status = 500;
        }

        return [$status, $result];
    }

    private function runImport(Request $request): string
    {
        if ($this->token) {
            if ($this->token !== $request->get('token')) {
                throw new InvalidArgumentException('Token validation failed');
            }
        }

        $data = json_decode($request->getBody(), true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Failed to decode payload');
        }

        return $this->saver->save($data);
    }
}
