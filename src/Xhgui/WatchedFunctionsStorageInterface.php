<?php

interface Xhgui_WatchedFunctionsStorageInterface {

    public function getWatchedFunctions();

    public function addWatchedFunction($name);

    public function updateWatchedFunction($id, $name);

    public function removeWatchedFunction($id);
}
