<?php

namespace XHGui\Test;

use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use XHGui\Saver\SaverInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use LazyContainerProperties;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupProperties();
    }

    /**
     * Load a fixture into the database.
     */
    protected function importFixture(SaverInterface $saver, string $fileName = 'normalized.json'): void
    {
        foreach ($this->loadFixture($fileName) as $record) {
            $saver->save($record, $record['_id'] ?? null);
        }
    }

    protected function loadFixture(string $fileName): array
    {
        $file = __DIR__ . '/fixtures/' . $fileName;
        $this->assertFileExists($file);
        $data = json_decode(file_get_contents($file), true);
        $this->assertNotEmpty($data);

        return $data;
    }

    protected function skipIfPdo($details = null): void
    {
        $saveHandler = $this->config['save.handler'];

        if ($saveHandler !== 'pdo') {
            return;
        }

        $message = 'PDO support is not complete';
        if ($details) {
            $message .= ': ' . $details;
        }
        $this->markTestIncomplete($message);
    }
    
    protected function buildRequest(array $env, array $post_data = array(), array $query_data = array())
    {
        
        $env     = Environment::mock($env);
        $request = Request::createFromEnvironment($env);
    
        if (count($post_data)) {
            $post_body = json_encode($post_data);
            $stream = $request->getBody();
            $stream->write($post_body);
            $stream->rewind();
        }
        
        if (count($query_data)) {
            $request = $request->withQueryParams($query_data);
        }
        
        return $request;
    
    }
    
    protected function getMockReponse()
    {
        /** @var Response $response */
        $response = $this->getMockBuilder(Response::class)
            ->setMethods(['withRedirect'])
            ->getMock();
        
        return $response;
    }
}
