xhgui
=====

A graphical interface for XHProf data built on MongoDB.

This tool requires that [XHProf](http://pecl.php.net/package/xhprof) is installed, which is a PHP Extension that records and provides profiling data. XHGui (this tool) takes that information, saves it in MongoDB, and provides a convienent GUI for working with it.


System Requirements
===================

 * [XHProf](http://pecl.php.net/package/xhprof) to actually profile the data
 * [MongoDB PHP](http://pecl.php.net/package/mongo) MongoDB PHP extension
 * [MongoDB](http://www.mongodb.org/) MongoDB Itself
 * [mcrypt] (http://php.net/manual/en/book.mcrypt.php) PHP must be configured with mcrypt (which is a dependency of Slim)


Installation
============

Installing Xhgui requires 2 main steps. First is installing the `xhgui` front-end, and the second is profiling a web application/site.


Installing the xhgui ui
-----------------------

* Clone or download `xhgui` from github.
* You'll need to install `mongodb`, and `php-mongodb`, at least version 1.3.0 of the php extension is required.
* Point your webserver to the `webroot` directory.
* Set the permissions on the `cache` cache directory to allow the webserver to create files.
  If you're lazy `0777` will work. Run

  ```
  chmod -R 0777 cache
  ```

* If your mongodb setup requires a username + password, or isn't running on the default port + host.
  You'll need to update `config/config.php` so that it can connect to mongod.
* You may wish to add indexes (recommended but optional) to improve the performance, you'll need to do this by using mongo console

  On your command prompt (irrespective of Windows or \*nix), open mongo shell using command 'mongo' and follow below  commands to add the index:
  
  ```
  $ mongo
  > use xhprof
  > db.results.ensureIndex( { 'meta.SERVER.REQUEST_TIME' : -1 } )
  > db.results.ensureIndex( { 'profile.main().wt' : -1 } )
  > db.results.ensureIndex( { 'profile.main().mu' : -1 } )
  > db.results.ensureIndex( { 'profile.main().cpu' : -1 } )
  > db.results.ensureIndex( { 'meta.url' : 1 } )
  ```

  After adding indexes, you may notice you can navigate across pages faster.
* Run the install script. This will download composer and use it to install the dependencies for xhgui.

    ```
    cd path/to/xhgui
    php install.php
    ```

* Setup your webserver. See below for how to setup the rewrite rules for nginx + apache.

Configure webserver re-write rules
----------------------------------

Xhgui prefers to have URL rewriting enabled, but will work without it.
For Apache you can do the following to enable URL rewriting:

1. Make sure that an .htaccess override is allowed and that AllowOverride is
   set to All for the correct DocumentRoot.
2. Make sure you are loading up mod_rewrite correctly. You should see something like:

    ```
    LoadModule rewrite_module libexec/apache2/mod_rewrite.so
    ```

3. Xhgui comes with a `.htaccess` to enable the remaining rewrite rules.

For nginx & fast-cgi you can the following snippet as a start:

    ```
    server {
        listen   80;
        server_name example.com;

        # root directive should be global
        root   /var/www/example.com/public/xhgui/webroot/;
        index  index.php;

        location / {
            try_files $uri $uri/ /index.php?$uri&$args;
        }

        location ~ \.php$ {
            try_files $uri =404;
            include /etc/nginx/fastcgi_params;
            fastcgi_pass    127.0.0.1:9000;
            fastcgi_index   index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
    }
    ```


Profiling an application / site
-------------------------------

The simplest way to get an application profiled, is to use `external/header.php`.
This file is designed to be combined with PHP's [auto_prepend_file](http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file) directive. This can be enabled system-wide through `php.ini`. Alternatively, you can enable
`auto_prepend_file` per virtual host. With apache this would look like:

    <VirtualHost *:80>
        php_admin_value auto_prepend_file "/Users/markstory/Sites/xhgui/external/header.php"
        DocumentRoot "/Users/markstory/Sites/awesome-thing/app/webroot/"
        ServerName site.localhost
    </VirtualHost>

With Nginx in fastcgi mode you could use:

    server {
        listen 80;
        server_name site.localhost;
        root /Users/markstory/Sites/awesome-thing/app/webroot/;
        fastcgi_param PHP_VALUE "auto_prepend_file=/Users/markstory/Sites/xhgui/external/header.php";
     }

Limiting Mongo Disk Usage 
-------------------------

Disk usage can grow quickly, especially when profiling applications with large code bases, or that utilize larger frameworks. One technique to keep the growth in check is to have Mongo automatically delete profiling documents once they reach a certain age. Decide on a maximum profile document age in seconds, you may wish to choose a lower value in development (where you profile everything), than production (where you profile only a selection of documents). The following command instructs Mongo to delete documents over 5 days (432000 seconds) old.

      $ mongo
      > use xhprof
      > db.results.ensureIndex( { "meta.request_ts" : 1 }, { expireAfterSeconds : 432000 } )

Waterfall Display 
-----------------
The goal of the waterfall display is to recognize that concurrent requests can affect each other. Concurrent DB requests (or other resources), CPU intensive activies, or even locks on session files can become relevant. With an Ajax heavy applicaitons understanding the page build is far more complex than a single load, hopefully the waterfall can help. Remember: If you're only profiling a sample of requests the waterfall fills you with impolite lies. 

Some Notes:

 * There should probably be more indexes on MongoDB for this to be performant
 * It introduces storage of a new request_ts_micro value, as second level granularity doesn't work well with waterfalls
 * Still very much in alpha
 * Feedback and pull requests welcome :)

License
=======

Copyright (c) 2013 Mark Story & Paul Reinheimer

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
