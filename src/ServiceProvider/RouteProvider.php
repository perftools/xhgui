<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use XHGui\Controller;

class RouteProvider implements ServiceProviderInterface
{
    public function register(Container $di): void
    {
        $this->registerRoutes($di, $di['app']);
        $this->registerControllers($di);
    }

    private function registerRoutes(Container $di, App $app): void
    {
        /*
        $app->error(static function (Exception $e) use ($di, $app): void {
            // @var Twig $view
            $view = $di['view'];
            $view->parserOptions['cache'] = false;
            $view->parserExtensions = [
                new TwigExtension($app),
            ];

            $app->view($view);
            $app->render('error/view.twig', [
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        });
        */

        // Profile Runs routes
        $app->get('/', function (Request $request, Response $response) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];

            // The list changes whenever new profiles are recorded.
            // Generally avoid caching, but allow re-use in browser's bfcache
            // and by cache proxies for concurrent requests.
            // https://github.com/perftools/xhgui/issues/261
            $response = $response->withHeader('Cache-Control', 'public, max-age=0');

            $controller->index($request, $response);
        })->setName('home');

        $app->get('/run/view', function (Request $request, Response $response) use ($di): void {
            // Permalink views to a specific run are meant to be public and immutable.
            // But limit the cache to only a short period of time (enough to allow
            // handling of abuse or other stampedes). This way we don't have to
            // deal with any kind of purging system for when profiles are deleted,
            // or for after XHGui itself is upgraded and static assets may be
            // incompatible etc.
            // https://github.com/perftools/xhgui/issues/261
            $response = $response->withHeader('Cache-Control', 'public, max-age=0');

            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->view($request, $response);
        })->setName('run.view');

        $app->get('/run/delete', function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteForm($request);
        })->setName('run.delete.form');

        $app->post('/run/delete', function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteSubmit($request);
        })->setName('run.delete.submit');

        $app->get('/run/delete_all', function () use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteAllForm();
        })->setName('run.deleteAll.form');

        $app->post('/run/delete_all', function () use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteAllSubmit();
        })->setName('run.deleteAll.submit');

        $app->get('/url/view', function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->url($request);
        })->setName('url.view');

        $app->get('/run/compare', function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->compare($request);
        })->setName('run.compare');

        $app->get('/run/symbol', function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->symbol($request);
        })->setName('run.symbol');

        $app->get('/run/symbol/short', function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->symbolShort($request);
        })->setName('run.symbol-short');

        $app->get('/run/callgraph', function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->callgraph($request);
        })->setName('run.callgraph');

        $app->get('/run/callgraph/data', function (Request $request, Response $response) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->callgraphData($request, $response);
        })->setName('run.callgraph.data');

        $app->get('/run/callgraph/dot', function (Request $request, Response $response) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->callgraphDataDot($request, $response);
        })->setName('run.callgraph.dot');

        // Import route
        $app->post('/run/import', function (Request $request, Response $response) use ($di): void {
            /** @var Controller\ImportController $controller */
            $controller = $di[Controller\ImportController::class];
            $controller->import($request, $response);
        })->setName('run.import');

        // Watch function routes.
        $app->get('/watch', function () use ($di): void {
            /** @var Controller\WatchController $controller */
            $controller = $di[Controller\WatchController::class];
            $controller->get();
        })->setName('watch.list');

        $app->post('/watch', function (Request $request) use ($di): void {
            /** @var Controller\WatchController $controller */
            $controller = $di[Controller\WatchController::class];
            $controller->post($request);
        })->setName('watch.save');

        // Custom report routes.
        $app->get('/custom', function () use ($di): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->get();
        })->setName('custom.view');

        $app->get('/custom/help', function (Request $request) use ($di): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->help($request);
        })->setName('custom.help');

        $app->post('/custom/query', function (Request $request, Response $response) use ($di): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->query($request, $response);
        })->setName('custom.query');

        // Waterfall routes
        $app->get('/waterfall', function () use ($di): void {
            /** @var Controller\WaterfallController $controller */
            $controller = $di[Controller\WaterfallController::class];
            $controller->index();
        })->setName('waterfall.list');

        $app->get('/waterfall/data', function (Request $request, Response $response) use ($di): void {
            /** @var Controller\WaterfallController $controller */
            $controller = $di[Controller\WaterfallController::class];
            $controller->query($request, $response);
        })->setName('waterfall.data');

        // Metrics
        $app->get('/metrics', function (Request $request, Response $response) use ($di): void {
            /** @var Controller\MetricsController $controller */
            $controller = $di[Controller\MetricsController::class];
            $controller->metrics($response);
        })->setName('metrics');
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
