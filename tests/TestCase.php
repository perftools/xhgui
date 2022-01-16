<?php

namespace XHGui\Test;

use Slim\Http\Request;
use XHGui\RequestProxy;
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

    protected function createRequest(array $query): RequestProxy
    {
        $request = Request::createFromEnvironment($this->env);
        $request = $request->withQueryParams($query);

        return new RequestProxy($request);
    }

    protected function createPostRequest(array $post): RequestProxy
    {
        $request = Request::createFromEnvironment($this->env);
        $request = $request->withParsedBody($post);

        return new RequestProxy($request);
    }

    protected function createJsonPostRequest(array $data): RequestProxy
    {
        $request = Request::createFromEnvironment($this->env);
        $stream = $request->getBody();
        $stream->write(json_encode($data));
        $stream->rewind();

        return new RequestProxy($request);
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
}
