<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Slim\App;
use XHGui\Controller;
use XHGui\RequestProxy as Request;
use XHGui\ResponseProxy as Response;

class RouteProvider implements ServiceProviderInterface
{
    public function register(Container $di): void
    {
        $this->registerRoutes($di, $di['app']);
        $this->registerControllers($di);
    }

    private function registerRoutes(Container $di, App $app): void
    {
        /**
         * Wrap Request/Response with RequestProxy/RequestWrapper
         */
        $wrap = static function ($handler) use ($di, $app) {
            return function () use ($handler, $di, $app) {
                $container = $app->getContainer();
                $request = $container->get('request.proxy');
                $response = $container->get('response.proxy');

                $handler($di, $request, $response);

                return $container->get('response.final');
            };
        };

        // Profile Runs routes
        $app->get('/', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];

            // The list changes whenever new profiles are recorded.
            // Generally avoid caching, but allow re-use in browser's bfcache
            // and by cache proxies for concurrent requests.
            // https://github.com/perftools/xhgui/issues/261
            $response->setHeader('Cache-Control', 'public, max-age=0');

            $controller->index($request);
        }))->setName('home');

        $app->get('/run/view', $wrap(function ($di, Request $request, Response $response): void {
            // Permalink views to a specific run are meant to be public and immutable.
            // But limit the cache to only a short period of time (enough to allow
            // handling of abuse or other stampedes). This way we don't have to
            // deal with any kind of purging system for when profiles are deleted,
            // or for after XHGui itself is upgraded and static assets may be
            // incompatible etc.
            // https://github.com/perftools/xhgui/issues/261
            $response->setHeader('Cache-Control', 'public, max-age=0');

            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->view($request);
        }))->setName('run.view');

        $app->get('/run/delete', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteForm($request);
        }))->setName('run.delete.form');

        $app->post('/run/delete', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteSubmit($request);
        }))->setName('run.delete.submit');

        $app->get('/run/delete_all', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteAllForm();
        }))->setName('run.deleteAll.form');

        $app->post('/run/delete_all', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteAllSubmit();
        }))->setName('run.deleteAll.submit');

        $app->get('/url/view', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->url($request);
        }))->setName('url.view');

        $app->get('/run/compare', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->compare($request);
        }))->setName('run.compare');

        $app->get('/run/symbol', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->symbol($request);
        }))->setName('run.symbol');

        $app->get('/run/symbol/short', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->symbolShort($request);
        }))->setName('run.symbol-short');

        $app->get('/run/callgraph', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->callgraph($request);
        }))->setName('run.callgraph');

        $app->get('/run/callgraph/data', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];

            $callgraph = $controller->callgraphData($request);

            $response
                ->setHeader('Content-Type', 'application/json')
                ->writeJson($callgraph);
        }))->setName('run.callgraph.data');

        $app->get('/run/callgraph/dot', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];

            $callgraph = $controller->callgraphDataDot($request);

            $response
                ->setHeader('Content-Type', 'application/json')
                ->writeJson($callgraph);
        }))->setName('run.callgraph.dot');

        // Import route
        $app->post('/run/import', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\ImportController $controller */
            $controller = $di[Controller\ImportController::class];

            [$status, $result] = $controller->import($request);

            $response
                ->setHeader('Content-Type', 'application/json')
                ->setStatus($status)
                ->writeJson($result);
        }))->setName('run.import');

        // Watch function routes.
        $app->get('/watch', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\WatchController $controller */
            $controller = $di[Controller\WatchController::class];
            $controller->get();
        }))->setName('watch.list');

        $app->post('/watch', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\WatchController $controller */
            $controller = $di[Controller\WatchController::class];
            $controller->post($request);
        }))->setName('watch.save');

        // Custom report routes.
        $app->get('/custom', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->get();
        }))->setName('custom.view');

        $app->get('/custom/help', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->help($request);
        }))->setName('custom.help');

        $app->post('/custom/query', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $query = $request->post('query');
            $retrieve = $request->post('retrieve');

            $result = $controller->query($query, $retrieve);

            $response
                ->setHeader('Content-Type', 'application/json')
                ->writeJson($result);
        }))->setName('custom.query');

        // Waterfall routes
        $app->get('/waterfall', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\WaterfallController $controller */
            $controller = $di[Controller\WaterfallController::class];
            $controller->index($request);
        }))->setName('waterfall.list');

        $app->get('/waterfall/data', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\WaterfallController $controller */
            $controller = $di[Controller\WaterfallController::class];

            $data = $controller->query($request);

            $response
                ->setHeader('Content-Type', 'application/json')
                ->writeJson($data);
        }))->setName('waterfall.data');

        // Metrics
        $app->get('/metrics', $wrap(function ($di, Request $request, Response $response): void {
            /** @var Controller\MetricsController $controller */
            $controller = $di[Controller\MetricsController::class];

            $body = $controller->metrics();

            $response
                ->setHeader('Content-Type', 'text/plain; version=0.0.4')
                ->write($body);
        }))->setName('metrics');
    }

    private function registerControllers(Container $app): void
    {
        $app[Controller\WatchController::class] = $app->factory(static function ($app) {
            return new Controller\WatchController($app['app'], $app['searcher']);
        });

        $app[Controller\RunController::class] = $app->factory(static function ($app) {
            return new Controller\RunController($app['app'], $app['searcher']);
        });

        $app[Controller\CustomController::class] = $app->factory(static function ($app) {
            return new Controller\CustomController($app['app'], $app['searcher']);
        });

        $app[Controller\WaterfallController::class] = $app->factory(static function ($app) {
            return new Controller\WaterfallController($app['app'], $app['searcher']);
        });

        $app[Controller\ImportController::class] = $app->factory(static function ($app) {
            return new Controller\ImportController($app['app'], $app['saver'], $app['config']['upload.token']);
        });

        $app[Controller\MetricsController::class] = $app->factory(static function ($app) {
            return new Controller\MetricsController($app['app'], $app['searcher']);
        });
    }
}
