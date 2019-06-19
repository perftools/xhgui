<?php

/**
 * Interface that all storage classes must implement
 */
interface Xhgui_StorageInterface
{
    /**
     * Find profiles based on filter.
     *
     * @param Xhgui_Storage_Filter $filter
     * @param bool $projections
     * @return mixed
     */
    public function find(\Xhgui_Storage_Filter $filter, $projections = false);

    /**
     * Count number of profiles that match given filter
     *
     * @param Xhgui_Storage_Filter $filter
     * @return mixed
     */
    public function count(\Xhgui_Storage_Filter $filter);

    /**
     * Return aggregated result
     *
     * @param Xhgui_Storage_Filter $filter
     * @param $col
     * @param int $percentile
     * @return mixed
     */
    public function aggregate(\Xhgui_Storage_Filter $filter, $col, $percentile = 1);

    /**
     * Get one profile by id
     *
     * @param $id
     * @return mixed
     */
    public function findOne($id);

    /**
     * Delete one profile by id
     *
     * @param $id
     * @return mixed
     */
    public function remove($id);

    /**
     * Drop all profiles
     *
     * @return mixed
     */
    public function drop();

    /**
     * Add one profile
     *
     * @param array $data
     * @return mixed
     */
    public function insert(array $data);

    /**
     * Update profile by id with given data
     *
     * @param $_id
     * @param array $data
     * @return mixed
     */
    public function update($_id, array $data);
}
