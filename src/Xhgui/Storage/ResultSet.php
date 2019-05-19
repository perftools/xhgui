<?php

class Xhgui_Storage_ResultSet implements \Iterator {

    protected $data = [];
    protected $keys = [];

    protected $i = 0;
    protected $limit = 25;
    protected $totalRows = 0;
    public function __construct($data = null, $totalRows = 0) {
        $this->data         = $data;
        $this->keys         = array_keys($data);
        $this->totalRows    = $totalRows;
    }

    public function toArray(){
        return $this->data;
    }

    public function count(){
        return count($this->data);
    }

    public function sort(){
        return $this;
    }

    public function skip($count){
        $this->i += $count;
        return $this;
    }

    public function limit($limit){
        $this->limit = $limit;
        return $this;
    }

    public function get($i) {
        return $this->data[$i];
    }
    /**
     * Return the current element
     */
    public function current() {
        return $this->get($this->keys[$this->i]);
    }

    /**
     * Move forward to next element
     */
    public function next() {
        $this->i++;
    }

    /**
     * Return the key of the current element
     */
    public function key() {
        return $this->keys[$this->i];
    }

    /**
     * Checks if current position is valid
     * 
     * Returns true on success or false on failure.
     */
    public function valid() {
        return !empty($this->keys[$this->i]) AND !empty($this->data[$this->keys[$this->i]]) AND $this->i < $this->limit;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind() {
        $this->i = 0;
    }

    /**
     * @return int
     */
    public function getTotalRows() {
        return $this->totalRows;
    }

    /**
     * @param int $totalRows
     */
    public function setTotalRows($totalRows) {
        $this->totalRows = $totalRows;
    }
}
