<?php

interface Xhgui_StorageInterface {

    public function find(\Xhgui_Storage_Filter $filter, $projections = false);

    public function count(\Xhgui_Storage_Filter $filter);

    public function aggregate(\Xhgui_Storage_Filter $filter, $col, $percentile = 1);

    public function findOne($id);

    public function remove($id);

    public function drop();
}
