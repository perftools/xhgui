<?php

/**
 * Watched functions interface
 */
interface Xhgui_WatchedFunctionsStorageInterface {

    /**
     * Return list of watched functions
     * @return mixed
     */
    public function getWatchedFunctions();

    /**
     * Add new function to watched function list
     *
     * @param $name
     * @return mixed
     */
    public function addWatchedFunction($name);

    /**
     * Update watched function by id with given id
     *
     * @param $id
     * @param $name
     * @return mixed
     */
    public function updateWatchedFunction($id, $name);

    /**
     * Remove watched function function by id
     *
     * @param $id
     * @return mixed
     */
    public function removeWatchedFunction($id);
}
