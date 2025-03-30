<?php

namespace XHGui\Saver;

use RuntimeException;

class NormalizingSaver implements SaverInterface
{
    public function __construct(private SaverInterface $saver)
    {
    }

    public function save(array $data, string $id = null): string
    {
        // extract "get" from "url"
        // profiler no longer needs to send "get" over the wire.
        // the individual savers may choose not to store this down separately
        $url = $data['meta']['url'];
        if (!$url) {
            throw new RuntimeException('No url provided');
        }
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $get);
        $data['meta']['get'] = $get;

        foreach ($data['profile'] as $index => &$profile) {
            // skip empty profiling
            if (!$profile) {
                unset($data['profile'][$index]);
                continue;
            }

            // normalize, fill all missing keys
            $profile += [
                'ct' => 0,
                'wt' => 0,
                'cpu' => 0,
                'mu' => 0,
                'pmu' => 0,
            ];
        }
        unset($profile);

        if (!$data['profile']) {
            throw new RuntimeException('Skipping to save empty profiling');
        }

        return $this->saver->save($data, $id);
    }
}
