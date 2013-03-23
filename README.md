xhgui
=====

A graphical interface for XHProf data built on MongoDB.

This tool requires that [XHProf](http://pecl.php.net/package/xhprof) is installed, which is a PHP Extension that records and provides profiling data. XHGui (this tool) takes that information, saves it in MongoDB, and provides a convienent GUI for working with it.


System Requirements
===================

 * [XHProf](http://pecl.php.net/package/xhprof) to actually profile the data
 * [MongoDB PHP](http://pecl.php.net/package/mongo) MongoDB PHP extension
 * [MongoDB](http://www.mongodb.org/) MongoDB Itself


Installation
============

Installing Xhgui requires 2 main steps. First is installing the `xhgui` front-end, and the second is profiling a web application/site.


Installing the xhgui ui
-----------------------

* Clone or download `xhgui` from github.
* You'll need to install mongodb, and php-mongodb, at least version 1.3.0 is required.
* Point your webserver to folder `web/webroot` 
* Set the permissions on `web/cache` to allow the webserver to create files.
  If you're lazy `0777` will work. Run

      chmod -R 0777 web/cache

* If your mongodb setup requires a username + password, or isn't running on the default port + host.
  You'll need to update `web/config/config.php` so that it can connect to mongod.
* You may wish to add indexes (recommended but optional) to improve the performance, you'll need to do this by using mongo console
  
  On your command prompt (irrespective of Windows or *nix), open mongo shell using command 'mongo' and follow below commands to add the index

		  $ mongo
		  > use xhprof
		  > db.results.ensureIndex( { 'meta.SERVER.REQUEST_TIME' : -1 } )
		  > db.results.ensureIndex( { 'profile.main().wt' : -1 } )
		  > db.results.ensureIndex( { 'profile.main().mu' : -1 } )
		  > db.results.ensureIndex( { 'profile.main().cpu' : -1 } )
		  > db.results.ensureIndex( { 'meta.url' : 1 } )
  
  That's it you added the indexes, you may notice now you are able navigate across pages faster

Profiling an application / site
-------------------------------

The simplest way to get an application profiled, is to use `external/header.php`.
This file is designed to be combined with PHP's [auto_prepend_file](http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file)
directive. This can be enabled system-wide through `php.ini`. Alternatively, you can enable
`auto_prepend_file` per virtual host. With apache this would look like:

    <VirtualHost *:80>
        php_admin_value auto_prepend_file "/Users/markstory/Sites/xhgui/external/header.php"
        DocumentRoot "/Users/markstory/Sites/awesome-thing/app/webroot/"
        ServerName site.localhost
    </VirtualHost>


