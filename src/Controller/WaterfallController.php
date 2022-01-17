<?php

namespace XHGui\Controller;

use Slim\App;
use XHGui\AbstractController;
use XHGui\Options\SearchOptions;
use XHGui\Profile;
use XHGui\RequestProxy as Request;
use XHGui\Searcher\SearcherInterface;

class WaterfallController extends AbstractController
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

    public function index($request): void
    {
        $search = [];
        $keys = ['remote_addr', 'request_start', 'request_end'];
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = trim($request->get($key));
            }
        }
        $result = $this->searcher->getAll(new SearchOptions([
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => true,
        ]));

        $paging = [
            'total_pages' => $result['totalPages'],
            'page' => $result['page'],
            'sort' => 'asc',
            'direction' => $result['direction'],
        ];

        $this->render('waterfall/list.twig', [
            'runs' => $result['results'],
            'search' => $search,
            'paging' => $paging,
            'base_url' => 'waterfall.list',
        ]);
    }

    public function query(Request $request)
    {
        $search = [];
        $keys = ['remote_addr', 'request_start', 'request_end'];
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }
        $result = $this->searcher->getAll(new SearchOptions([
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => true,
        ]));
        $data = [];
        /** @var Profile $r */
        foreach ($result['results'] as $r) {
            $duration = $r->get('main()', 'wt');
            $start = $r->getMeta('SERVER.REQUEST_TIME_FLOAT');
            $title = $r->getMeta('url');
            $data[] = [
                'id' => $r->getId(),
                'title' => $title,
                'start' => $start * 1000,
                'duration' => $duration / 1000, // Convert to correct scale
            ];
        }

        return $data;
    }
}
