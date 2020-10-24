<?php

namespace XHGui\Saver;

class NormalizingSaver implements SaverInterface
{
    private $saver;

    public function __construct(SaverInterface $saver)
    {
        $this->saver = $saver;
    }

    public function save(array $data, string $id = null): string
    {
        foreach ($data['profile'] as $index => &$profile) {
            // normalize, fill all missing keys
            $profile += [
                'ct' => 0,
                'wt' => 0,
                'cpu' => 0,
                'mu' => 0,
                'pmu' => 0,
            ];
        }

        return $this->saver->save($data, $id);
    }
}
