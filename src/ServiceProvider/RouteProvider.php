<?php

namespace XHGui\ServiceProvider;

use Exception;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Slim\Slim as App;
use Slim\Views\Twig;
use XHGui\Controller;
use XHGui\Twig\TwigExtension;

class RouteProvider implements ServiceProviderInterface
{
    public function register(Container $di): void
    {
        $this->registerRoutes($di, $di['app']);
        $this->registerControllers($di);
    }

    private function registerRoutes(Container $di, App $app): void
    {
        $app->error(static function (Exception $e) use ($di, $app): void {
            /** @var Twig $view */
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

        // Profile Runs routes
        $app->get('/', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();
            $response = $app->response();

            // The list changes whenever new profiles are recorded.
            // Generally avoid caching, but allow re-use in browser's bfcache
            // and by cache proxies for concurrent requests.
            // https://github.com/perftools/xhgui/issues/261
            $response->headers->set('Cache-Control', 'public, max-age=0');

            $controller->index($request);
        })->setName('home');

        $app->get('/run/view', static function () use ($di, $app): void {
            $request = $app->request();
            $response = $app->response();

            // Permalink views to a specific run are meant to be public and immutable.
            // But limit the cache to only a short period of time (enough to allow
            // handling of abuse or other stampedes). This way we don't have to
            // deal with any kind of purging system for when profiles are deleted,
            // or for after XHGui itself is upgraded and static assets may be
            // incompatible etc.
            // https://github.com/perftools/xhgui/issues/261
            $response->headers->set('Cache-Control', 'public, max-age=60, must-revalidate');

            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->view($request);
        })->setName('run.view');

        $app->get('/run/delete', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();

            $controller->deleteForm($request);
        })->setName('run.delete.form');

        $app->post('/run/delete', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();

            $controller->deleteSubmit($request);
        })->setName('run.delete.submit');

        $app->get('/run/delete_all', static function () use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteAllForm();
        })->setName('run.deleteAll.form');

        $app->post('/run/delete_all', static function () use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteAllSubmit();
        })->setName('run.deleteAll.submit');

        $app->get('/url/view', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();

            $controller->url($request);
        })->setName('url.view');

        $app->get('/run/compare', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();

            $controller->compare($request);
        })->setName('run.compare');

        $app->get('/run/symbol', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();

            $controller->symbol($request);
        })->setName('run.symbol');

        $app->get('/run/symbol/short', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();

            $controller->symbolShort($request);
        })->setName('run.symbol-short');

        $app->get('/run/callgraph', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();

            $controller->callgraph($request);
        })->setName('run.callgraph');

        $app->get('/run/callgraph/data', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();
            $response = $app->response();

            $callgraph = $controller->callgraphData($request, $response);

            $response['Content-Type'] = 'application/json';
            $response->body(json_encode($callgraph));
        })->setName('run.callgraph.data');

        $app->get('/run/callgraph/dot', static function () use ($di, $app): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $request = $app->request();
            $response = $app->response();

            $callgraph = $controller->callgraphDataDot($request);

            $response['Content-Type'] = 'application/json';
            $response->body(json_encode($callgraph));
        })->setName('run.callgraph.dot');

        // Import route
        $app->post('/run/import', static function () use ($di, $app): void {
            /** @var Controller\ImportController $controller */
            $controller = $di[Controller\ImportController::class];
            $request = $app->request();
            $response = $app->response();

            $result = $controller->import($request, $response);

            $response['Content-Type'] = 'application/json';
            $response->body(json_encode($result));
        })->setName('run.import');

        // Watch function routes.
        $app->get('/watch', static function () use ($di): void {
            /** @var Controller\WatchController $controller */
            $controller = $di[Controller\WatchController::class];
            $controller->get();
        })->setName('watch.list');

        $app->post('/watch', static function () use ($di, $app): void {
            /** @var Controller\WatchController $controller */
            $controller = $di[Controller\WatchController::class];
            $request = $app->request();

            $controller->post($request);
        })->setName('watch.save');

        // Custom report routes.
        $app->get('/custom', static function () use ($di): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->get();
        })->setName('custom.view');

        $app->get('/custom/help', static function () use ($di, $app): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $request = $app->request();

            $controller->help($request);
        })->setName('custom.help');

        $app->post('/custom/query', static function () use ($di, $app): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $request = $app->request();
            $response = $app->response();

            $query = $request->post('query');
            $retrieve = $request->post('retrieve');

            $result = $controller->query($query, $retrieve);

            $response->body(json_encode($result));
            $response['Content-Type'] = 'application/json';
        })->setName('custom.query');

        // Waterfall routes
        $app->get('/waterfall', static function () use ($di): void {
            /** @var Controller\WaterfallController $controller */
            $controller = $di[Controller\WaterfallController::class];
            $controller->index();
        })->setName('waterfall.list');

        $app->get('/waterfall/data', static function () use ($di, $app): void {
            /** @var Controller\WaterfallController $controller */
            $controller = $di[Controller\WaterfallController::class];
            $request = $app->request();
            $response = $app->response();

            $controller->query($request, $response);
        })->setName('waterfall.data');

        // Metrics
        $app->get('/metrics', static function () use ($di, $app): void {
            /** @var Controller\MetricsController $controller */
            $controller = $di[Controller\MetricsController::class];
            $response = $app->response();

            $body = $controller->metrics();

            $response->body($body);
            $response['Content-Type'] = 'text/plain; version=0.0.4';
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
