<?php

class Xhgui_Util
{
    /**
     * Returns an new ObjectId-like string, where its first 8
     * characters encode the current unix timestamp and the
     * next 16 are random.
     *
     * @see http://php.net/manual/en/mongodb-bson-objectid.construct.php
     *
     * @return string
     */
    public static function generateId()
    {
        return dechex(time()) . bin2hex(random_bytes(8));
    }
}
