xhgui
=====

A graphical interface for XHProf data built on MongoDB.

This tool requires that [XHProf](http://pecl.php.net/package/xhprof) is installed, which is a PHP Extension that records and provides profiling data. XHGui (this tool) takes that information, saves it in MongoDB, and provides a convienent GUI for working with it.


System Requirements
-------------------

 * [XHProf](http://pecl.php.net/package/xhprof) to actually profile the data
 * [MongoDB PHP](http://pecl.php.net/package/mongo) MongoDB PHP extension
 * [MongoDB](http://www.mongodb.org/) MongoDB Itself

Installation
------------

* Configure mongodb?
* Set the permissions on `web/cache` to allow the webserver to create files.
  If you're lazy `0777` will work. Run

      chmod -R 0777 web/cache
* You may want to look at [header.php](./blob/master/external/header.php) for an easy way to start profiling your code
* Profit!?
