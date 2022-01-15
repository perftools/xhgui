<?php

namespace XHGui\Controller;

use Slim\App;
use Slim\Http\Request;
use XHGui\AbstractController;
use XHGui\Searcher\SearcherInterface;
use XHGui\Twig\TwigExtension;
use Slim\Flash\Messages;

class WatchController extends AbstractController
{
    /**
     * @var SearcherInterface
     */
    protected $searcher;

    public function __construct(App $app, SearcherInterface $searcher)
    {
        parent::__construct($app);
        $this->searcher = $searcher;

    }

    public function get(): void
    {
        $watched = $this->searcher->getAllWatches();

        $this->render('watch/list.twig', ['watched' => $watched]);
    }

    public function post(Request $request): void
    {
        $saved = false;
        foreach ((array)$request->getParsedBodyParam('watch') as $data) {
            $saved = true;
            $this->searcher->saveWatch($data);
        }
        if ($saved) {
            $flash = $this->app->getContainer()->get('flash');
            $flash->addMessage('success', 'Watch functions updated.');
        }
        $twig = $this->app->getContainer()->get(TwigExtension::class);
        $this->app->redirect('/', $twig->url('watch.list'));
    }
}
