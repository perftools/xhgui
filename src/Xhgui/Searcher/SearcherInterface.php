<?php

namespace XHGui\Searcher;

use Exception;
use MongoCursor;
use XHGui\Profile;

/**
 * Contract for classes that search and retrieve XHProf profiles.
 */
interface SearcherInterface
{
    const DEFAULT_DIRECTION = 'desc';

    /**
     * Get the latest profile data.
     *
     * @throws Exception when there is not even a single profile available
     * @return Profile
     */
    public function latest();

    /**
     * Run a custom query and return the result as an array.
     *
     * @param array      $conditions
     * @param array|null $fields
     * @param int        $limit
     *
     * @return array
     */
    public function query($conditions, $limit, $fields = []);

    /**
     * Get a single profile run by id.
     *
     * @param string $id the id of the profile to get
     * @throws Exception when a profile with the given $id is not found
     * @return Profile
     */
    public function get($id);

    /**
     * Get the list of profiles for a simplified url.
     *
     * @param string $url the url to load profiles for
     * @param array $options pagination options to use
     * @param array $conditions the search options
     * @return MongoCursor
     */
    public function getForUrl($url, $options, $conditions = []);

    /**
     * Get the Percentile metrics for a URL
     *
     * This will group data by date and returns only the
     * percentile + date, making the data ideal for time series graphs
     *
     * @param int $percentile The percentile you want. e.g. 90.
     * @param string $url
     * @param array $search Search options containing date_start and or date_end
     * @return array Array of metrics grouped by date
     */
    public function getPercentileForUrl($percentile, $url, $search = []);

    /**
     * Get the Average metrics for a URL
     *
     * This will group data by date and returns only the
     * avg + date, making the data ideal for time series graphs
     *
     * @param string $url
     * @param array $search Search options containing date_start and or date_end
     * @return array Array of metrics grouped by date
     */
    public function getAvgsForUrl($url, $search = []);

    /**
     * Get a paginated set of results.
     *
     * @param array $options the find options to use
     * @return array An array of result data with the following keys:
     *  - results:    an array of Profile objects
     *  - sort:       an array of search criteria (TODO meta.SERVER.REQUEST_TIME => -1 ????)
     *  - direction:  an string, either 'desc' or 'asc'
     *  - page:       an integer, the page to display (e.g. 3)
     *  - perPage:    an integer, how many profiles to display per page (e.g. 25)
     *  - totalPages: an integer, total number of pages (e.g. 10)
     */
    public function getAll($options = []);

    /**
     * Delete a profile run.
     *
     * @param string $id the profile id to delete
     */
    public function delete($id);

    /**
     * Used to truncate a collection.
     *
     * Primarily used in test cases to reset the test db.
     *
     * @return bool
     */
    public function truncate();

    /**
     * Save a value to the collection.
     *
     * Will do an insert or update depending
     * on the id field being present.
     *
     * @param array $data the data to save
     * @return bool
     */
    public function saveWatch(array $data);

    /**
     * Get all the known watch functions.
     *
     * @return array array of watch functions
     */
    public function getAllWatches();

    /**
     * Truncate the watch collection.
     */
    public function truncateWatches();

    /**
     * Return statistics about the size of all profiling data.
     *
     * @return array array of stats
     */
    public function stats();
}
