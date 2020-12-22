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
    public function register(Container $di)
    {
        $this->registerRoutes($di, $di['app']);
    }

    private function registerRoutes(Container $di, App $app)
    {
        $app->error(static function (Exception $e) use ($di, $app) {
            /** @var Twig $view */
            $view = $di['view'];
            $view->parserOptions['cache'] = false;
            $view->parserExtensions = [
                new TwigExtension($app),
            ];

            // Remove the controller so we don't render it.
            unset($app->controller);

            $app->view($view);
            $app->render('error/view.twig', [
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        });

        // Profile Runs routes
        $app->get('/', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();
            $response = $app->response();

            $controller->index($request, $response);
        })->setName('home');

        $app->get('/run/view', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();
            $response = $app->response();

            $controller->view($request, $response);
        })->setName('run.view');

        $app->get('/run/delete', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();

            $controller->deleteForm($request);
        })->setName('run.delete.form');

        $app->post('/run/delete', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();

            $controller->deleteSubmit($request);
        })->setName('run.delete.submit');

        $app->get('/run/delete_all', static function () use ($di) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $controller->deleteAllForm();
        })->setName('run.deleteAll.form');

        $app->post('/run/delete_all', static function () use ($di) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $controller->deleteAllSubmit();
        })->setName('run.deleteAll.submit');

        $app->get('/url/view', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();

            $controller->url($request);
        })->setName('url.view');

        $app->get('/run/compare', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();

            $controller->compare($request);
        })->setName('run.compare');

        $app->get('/run/symbol', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();

            $controller->symbol($request);
        })->setName('run.symbol');

        $app->get('/run/symbol/short', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();

            $controller->symbolShort($request);
        })->setName('run.symbol-short');

        $app->get('/run/callgraph', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $app->controller = $di['runController'];
            $request = $app->request();

            $controller->callgraph($request);
        })->setName('run.callgraph');

        $app->get('/run/callgraph/data', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();
            $response = $app->response();

            $controller->callgraphData($request, $response);
        })->setName('run.callgraph.data');

        $app->get('/run/callgraph/dot', static function () use ($di, $app) {
            /** @var Controller\RunController $controller */
            $controller = $di['runController'];
            $request = $app->request();
            $response = $app->response();

            $controller->callgraphDataDot($request, $response);
        })->setName('run.callgraph.dot');

        // Import route
        $app->post('/run/import', static function () use ($di, $app) {
            /** @var Controller\ImportController $controller */
            $controller = $di['importController'];
            $request = $app->request();
            $response = $app->response();

            $controller->import($request, $response);
        })->setName('run.import');

        // Watch function routes.
        $app->get('/watch', static function () use ($di, $app) {
            /** @var Controller\WatchController $controller */
            $controller = $app->controller = $di['watchController'];
            $controller->get();
        })->setName('watch.list');

        $app->post('/watch', static function () use ($di, $app) {
            /** @var Controller\WatchController $controller */
            $controller = $di['watchController'];
            $request = $app->request();

            $controller->post($request);
        })->setName('watch.save');

        // Custom report routes.
        $app->get('/custom', static function () use ($di, $app) {
            /** @var Controller\CustomController $controller */
            $controller = $app->controller = $di['customController'];
            $controller->get();
        })->setName('custom.view');

        $app->get('/custom/help', static function () use ($di, $app) {
            /** @var Controller\CustomController $controller */
            $controller = $app->controller = $di['customController'];
            $request = $app->request();

            $controller->help($request);
        })->setName('custom.help');

        $app->post('/custom/query', static function () use ($di, $app) {
            /** @var Controller\CustomController $controller */
            $controller = $di['customController'];
            $request = $app->request();
            $response = $app->response();

            $controller->query($request, $response);
        })->setName('custom.query');

        // Waterfall routes
        $app->get('/waterfall', static function () use ($di, $app) {
            /** @var Controller\WaterfallController $controller */
            $controller = $app->controller = $di['waterfallController'];
            $controller->index();
        })->setName('waterfall.list');

        $app->get('/waterfall/data', static function () use ($di, $app) {
            /** @var Controller\WaterfallController $controller */
            $controller = $di['waterfallController'];
            $request = $app->request();
            $response = $app->response();

            $controller->query($request, $response);
        })->setName('waterfall.data');

        // Metrics
        $app->get('/metrics', static function () use ($di, $app) {
            /** @var Controller\MetricsController $controller */
            $controller = $di['metricsController'];
            $response = $app->response();

            $controller->metrics($response);
        })->setName('metrics');
    }
}
