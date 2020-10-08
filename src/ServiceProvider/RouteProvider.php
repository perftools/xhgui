<?php

namespace XHGui\ServiceProvider;

use Exception;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
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
        $app->get('/', static function (Request $request, Response $response) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->index($request, $response);
        })->setName('home');

        $app->get('/run/view', static function (Request $request, Response $response) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->view($request, $response);
        })->setName('run.view');

        $app->get('/run/delete', static function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->deleteForm($request);
        })->setName('run.delete.form');

        $app->post('/run/delete', static function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
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

        $app->get('/url/view', static function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->url($request);
        })->setName('url.view');

        $app->get('/run/compare', static function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->compare($request);
        })->setName('run.compare');

        $app->get('/run/symbol', static function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->symbol($request);
        })->setName('run.symbol');

        $app->get('/run/symbol/short', static function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->symbolShort($request);
        })->setName('run.symbol-short');

        $app->get('/run/callgraph', static function (Request $request) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->callgraph($request);
        })->setName('run.callgraph');

        $app->get('/run/callgraph/data', static function (Request $request, Response $response) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->callgraphData($request, $response);
        })->setName('run.callgraph.data');

        $app->get('/run/callgraph/dot', static function (Request $request, Response $response) use ($di): void {
            /** @var Controller\RunController $controller */
            $controller = $di[Controller\RunController::class];
            $controller->callgraphDataDot($request, $response);
        })->setName('run.callgraph.dot');

        // Import route
        $app->post('/run/import', static function (Request $request, Response $response) use ($di): void {
            /** @var Controller\ImportController $controller */
            $controller = $di[Controller\ImportController::class];
            $controller->import($request, $response);
        })->setName('run.import');

        // Watch function routes.
        $app->get('/watch', static function () use ($di): void {
            /** @var Controller\WatchController $controller */
            $controller = $di[Controller\WatchController::class];
            $controller->get();
        })->setName('watch.list');

        $app->post('/watch', static function (Request $request) use ($di): void {
            /** @var Controller\WatchController $controller */
            $controller = $di[Controller\WatchController::class];
            $controller->post($request);
        })->setName('watch.save');

        // Custom report routes.
        $app->get('/custom', static function () use ($di): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->get();
        })->setName('custom.view');

        $app->get('/custom/help', static function (Request $request) use ($di): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->help($request);
        })->setName('custom.help');

        $app->post('/custom/query', static function (Request $request, Response $response) use ($di): void {
            /** @var Controller\CustomController $controller */
            $controller = $di[Controller\CustomController::class];
            $controller->query($request, $response);
        })->setName('custom.query');

        // Waterfall routes
        $app->get('/waterfall', static function () use ($di): void {
            /** @var Controller\WaterfallController $controller */
            $controller = $di[Controller\WaterfallController::class];
            $controller->index();
        })->setName('waterfall.list');

        $app->get('/waterfall/data', static function (Request $request, Response $response) use ($di): void {
            /** @var Controller\WaterfallController $controller */
            $controller = $di[Controller\WaterfallController::class];
            $controller->query($request, $response);
        })->setName('waterfall.data');

        // Metrics
        $app->get('/metrics', static function (Request $request, Response $response) use ($di): void {
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
