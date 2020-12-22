<?php

namespace XHGui;

class Util
{
    /**
     * Returns an new ObjectId-like string, where its first 8
     * characters encode the current unix timestamp and the
     * next 16 are random.
     *
     * The length will always be 24 bytes.
     * NOTE: The above assumption will fail as the date value will overflow
     * past Feb 7 06:28:15 2106 UTC and Jan 19 03:14:07 2038 UTC on 32bit
     * systems
     *
     * @see https://php.net/manual/en/mongodb-bson-objectid.construct.php
     */
    public static function generateId(): string
    {
        return dechex(time()) . bin2hex(random_bytes(8));
    }
}
