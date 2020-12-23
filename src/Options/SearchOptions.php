<?php

namespace XHGui\Options;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use XHGui\Searcher\SearcherInterface;

class SearchOptions extends OptionsConfigurator
{
    /**
     * Options for SearchInterface::getAll
     *
     *  - sort:       an array of search criteria (TODO meta.SERVER.REQUEST_TIME => -1 ????)
     *  - direction:  an string, either 'desc' or 'asc'
     *  - page:       an integer, the page to display (e.g. 3)
     *  - perPage:    an integer, how many profiles to display per page (e.g. 25)
     *  - conditions: an array of criteria to match
     *  - projection: an array or bool
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        // NOTE: the null values is trickery to set default values via null value
        $defaults = [
            'sort' => null,
            'direction' => SearcherInterface::DEFAULT_DIRECTION,
            'page' => SearcherInterface::DEFAULT_PAGE,
            'perPage' => SearcherInterface::DEFAULT_PER_PAGE,
            'conditions' => [],
            'projection' => false,
        ];
        $resolver->setDefaults($defaults);
        $resolver->setRequired(['sort', 'direction', 'page', 'perPage']);

        $resolver->setAllowedTypes('sort', ['null', 'string']);
        $resolver->setAllowedTypes('direction', ['null', 'string']);
        $resolver->setAllowedTypes('page', 'int');
        $resolver->setAllowedTypes('perPage', ['null', 'int']);
        $resolver->setAllowedTypes('conditions', 'array');
        $resolver->setAllowedTypes('projection', ['bool', 'array']);

        $resolver->setAllowedValues('direction', [null, 'asc', 'desc']);
        $resolver->setAllowedValues('sort', [null, 'time', 'wt', 'cpu', 'mu', 'pmu']);

        $resolver->setNormalizer('direction', function (Options $options, $value) use ($defaults) {
            if (!$value) {
                return $defaults['direction'];
            }

            return $value;
        });
        $resolver->setNormalizer('perPage', function (Options $options, $value) use ($defaults) {
            if (!$value) {
                return $defaults['perPage'];
            }

            return (int)$value;
        });
        $resolver->setNormalizer('page', function (Options $options, $value) use ($defaults) {
            if (!$value || $value < 1) {
                return $defaults['page'];
            }

            return (int)$value;
        });
    }
}
