xhgui
=====

A graphical interface for XHProf data built on MongoDB.

This tool requires that [XHProf](http://pecl.php.net/package/xhprof) or 
its fork [Uprofiler](https://github.com/FriendsOfPHP/uprofiler) is installed, 
which is a PHP Extension that records and provides profiling data.
XHGui (this tool) takes that information, saves it in MongoDB, and provides
a convienent GUI for working with it.


System Requirements
===================

 * [XHProf](http://pecl.php.net/package/xhprof) or 
   [Uprofiler](https://github.com/FriendsOfPHP/uprofiler) to actually profile the data
 * [MongoDB PHP](http://pecl.php.net/package/mongo) MongoDB PHP extension
 * [MongoDB](http://www.mongodb.org/) MongoDB Itself
 * [mcrypt] (http://php.net/manual/en/book.mcrypt.php) PHP must be configured
   with mcrypt (which is a dependency of Slim)
 * [dom] (http://php.net/manual/en/book.dom.php) If you are running the tests
   you'll need the DOM extension (which is a dependency of PHPUnit)


Installation
============

Installing Xhgui requires 2 main steps. First is installing the `xhgui`
front-end, and the second is profiling a web application/site.


Installing Xhgui
----------------

* Clone or download `xhgui` from github.
* You'll need to install `mongodb`, and `php-mongodb`, at least version 1.3.0
  of the php extension is required.
* Point your webserver to the `webroot` directory.
* Set the permissions on the `cache` cache directory to allow the webserver to
  create files.  If you're lazy `0777` will work. Run:

  ```
  chmod -R 0777 cache
  ```

* If your mongodb setup requires a username + password, or isn't running on the
  default port + host.  You'll need to update `config/config.php` so that it
  can connect to mongod.
* You may wish to add indexes (recommended but optional) to improve the
  performance, you'll need to do this by using mongo console

  On your command prompt (irrespective of Windows or \*nix), open mongo shell
  using command 'mongo' and follow below  commands to add the index:

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
* Run the install script. This will download composer and use it to install the
  dependencies for xhgui.

    ```
    cd path/to/xhgui
    php install.php
    ```

* Setup your webserver. See below for how to setup the rewrite rules for nginx + apache.

Configuration
=============

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


Configure Xhgui profiling rate
-------------------------------

After installing Xhgui you may want to do change how frequently you profile the
host application. The `profiler.enable` configuration option allows you to
provide a callback function that determines which requests are profiled. By
default 1 in 100 requests are profiled. If for example you wanted to only profile requests
in a certain URL path you could do the following:


```php
// In config/config.php
return array(
    // Other config
    'profiler.enable' => function() {
        $url = $_SERVER['REQUEST_URI'];
        if (strpos($url, '/blog') === 0) {
            return false;
        }
        return rand(0, 100) === 42;
    }
);
```

The above code would profile anything not in `/blog` 1 in 100 requests. Paths containing `/blog` would never
be profiled. To profile *every* request you could do the following:

```php
// In config/config.php
return array(
    // Other config
    'profiler.enable' => function() {
        return true;
    }
);
```


Configure how 'simple' URLs are created
---------------------------------------

Xhgui generates 'simple' URLs for each profile collected. These simple URLs are used to generate
the aggregate data used on the URL view. Since different applications have different requirements
for how URLs map to logical blocks of code, a configuration option allows you to provide custom
logic to generate the simple URL. By default all numeric values in the query string are removed. To
provide custom logic you define the `profiler.simple_url` configuration option:

```php
// In config/config.php
return array(
    // Other config
    'profile.simple_url' => function($url) {
        // Your code goes here.
    }
);
```

The URL argument is the `REQUEST_URI` or `argv` value.


Profiling an application / site
===============================

The simplest way to get an application profiled, is to use
`external/header.php`.  This file is designed to be combined with PHP's
[auto_prepend_file](http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file)
directive. This can be enabled system-wide through `php.ini`. Alternatively,
you can enable `auto_prepend_file` per virtual host. With apache this would
look like:

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

Profiling a CLI script
======================

The simplest way to get a CLI script profiled, is to use
`external/header.php`.  This file is designed to be combined with PHP's
[auto_prepend_file](http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file)
directive. This can be enabled system-wide through `php.ini`. Alternatively,
you can enable include the `header.php` at the top of your script:

    <?php
    require '/path/to/xhgui/external/header.php';
    // Rest of script.

Or use the `-d` flag:

    php -d auto_prepend_file=/path/to/xhgui/external/header.php do_work.php


Saving & importing profiles
---------------------------

If your site cannot directly connect to your mongodb instance, you can choose
to save your data on a temporary file for a later import to xhgui's mongo
database.  Change the `save.handler` setting to `file` and define your file's
path with `save.handler.filename`.  To import a file inside mongodb use the
`external/import.php`. Be aware of file locking. Depending on your workload, 
you may need to change the `save.handler.filename` file path to avoid file locking


```
php external/import.php -f /path/to/file
```

Be careful, importing the same file twice will load twice the run datas inside
mongo, resulting with duplicate profiles


Limiting Mongo Disk Usage
-------------------------

Disk usage can grow quickly, especially when profiling applications with large
code bases, or that utilize larger frameworks. One technique to keep the growth
in check is to have Mongo automatically delete profiling documents once they
reach a certain age. Decide on a maximum profile document age in seconds, you
may wish to choose a lower value in development (where you profile everything),
than production (where you profile only a selection of documents). The
following command instructs Mongo to delete documents over 5 days (432000
seconds) old.

      $ mongo
      > use xhprof
      > db.results.ensureIndex( { "meta.request_ts" : 1 }, { expireAfterSeconds : 432000 } )

Waterfall Display
-----------------

The goal of the waterfall display is to recognize that concurrent requests can
affect each other. Concurrent DB requests (or other resources), CPU intensive
activies, or even locks on session files can become relevant. With an Ajax
heavy applicaitons understanding the page build is far more complex than
a single load, hopefully the waterfall can help. Remember: If you're only
profiling a sample of requests the waterfall fills you with impolite lies. 

Some Notes:

 * There should probably be more indexes on MongoDB for this to be performant
 * It introduces storage of a new request_ts_micro value, as second level
   granularity doesn't work well with waterfalls
 * Still very much in alpha
 * Feedback and pull requests welcome :)

Releases/Changelog
==================

See the [releases](https://github.com/preinheimer/xhgui/releases) for changelogs,
and release information.

License
=======

Copyright (c) 2013 Mark Story & Paul Reinheimer

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
