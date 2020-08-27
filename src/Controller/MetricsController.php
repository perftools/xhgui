<?php

namespace XHGui\Controller;

use Slim\App;
use Slim\Http\Response;
use XHGui\AbstractController;
use XHGui\Searcher\SearcherInterface;

class MetricsController extends AbstractController
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

    public function metrics(Response $response): void
    {
        $stats = $this->searcher->stats();

        $body = "# HELP xhgui_profiles_total Number of profiles collected.\n";
        $body .= "# TYPE xhgui_profiles_total gauge\n";
        $body .= sprintf("xhgui_profiles_total %0.1F\n\n", $stats['profiles']);

        $body .= "# HELP xhgui_profile_bytes_total Size of profiles collected.\n";
        $body .= "# TYPE xhgui_profile_bytes_total gauge\n";
        $body .= sprintf("xhgui_profile_bytes_total %0.1F\n\n", $stats['bytes']);

        $body .= "# HELP xhgui_latest_profile_seconds UNIX timestamp of most recent profile.\n";
        $body .= "# TYPE xhgui_latest_profile_seconds gauge\n";
        $body .= sprintf("xhgui_latest_profile_seconds %0.1F\n", $stats['latest']);

        $response->body($body);
        $response['Content-Type'] = 'text/plain; version=0.0.4';
    }
}
