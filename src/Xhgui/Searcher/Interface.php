<?php

/**
 * Contract for classes that search and retrieve XHProf profiles.
 */
interface Xhgui_Searcher_Interface
{
    /**
     * Get the latest profile data.
     *
     * @return Xhgui_Profile
     *
     * @throws Exception When there is not even a single profile available.
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
     * @param string $id The id of the profile to get.
     * @return Xhgui_Profile
     *
     * @throws Exception When a profile with the given $id is not found.
     */
    public function get($id);

    /**
     * Get the list of profiles for a simplified url.
     *
     * @param string $url The url to load profiles for.
     * @param array $options Pagination options to use.
     * @param array $conditions The search options.
     * @return MongoCursor
     */
    public function getForUrl($url, $options, $conditions = array());

    /**
     * Get the Percentile metrics for a URL
     *
     * This will group data by date and returns only the
     * percentile + date, making the data ideal for time series graphs
     *
     * @param integer $percentile The percentile you want. e.g. 90.
     * @param string $url
     * @param array $search Search options containing date_start and or date_end
     * @return array Array of metrics grouped by date
     */
    public function getPercentileForUrl($percentile, $url, $search = array());

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
    public function getAvgsForUrl($url, $search = array());

    /**
     * Get a paginated set of results.
     *
     * @param array $options The find options to use.
     * @return array An array of result data with the following keys:
     *  - results:    an array of Xhgui_Profile objects
     *  - sort:       an array of search criteria (TODO meta.SERVER.REQUEST_TIME => -1 ????)
     *  - direction:  an string, either 'desc' or 'asc'
     *  - page:       an integer, the page to display (e.g. 3)
     *  - perPage:    an integer, how many profiles to display per page (e.g. 25)
     *  - totalPages: an integer, total number of pages (e.g. 10)
     */
    public function getAll($options = array());

    /**
     * Delete a profile run.
     *
     * @param string $id The profile id to delete.
     */
    public function delete($id);

    /**
     * Used to truncate a collection.
     *
     * Primarily used in test cases to reset the test db.
     *
     * @return boolean
     */
    public function truncate();

    /**
     * Save a value to the collection.
     *
     * Will do an insert or update depending
     * on the id field being present.
     *
     * @param array $data The data to save.
     * @return boolean
     */
    public function saveWatch(array $data);

    /**
     * Get all the known watch functions.
     *
     * @return array Array of watch functions.
     */
    public function getAllWatches();

    /**
     * Truncate the watch collection.
     *
     * @return void
     */
    public function truncateWatches();
}
