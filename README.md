xhgui
=====

A graphical interface for XHProf data built on MongoDB.

This tool requires that [XHProf](http://pecl.php.net/package/xhprof) or its one
of its forks [Uprofiler](https://github.com/FriendsOfPHP/uprofiler),
[Tideways](https://github.com/tideways/php-profiler-extension) are installed.
XHProf is a PHP Extension that records and provides profiling data.
XHGui (this tool) takes that information, saves it in MongoDB, and provides
a convenient GUI for working with it.

[![Build Status](https://travis-ci.org/perftools/xhgui.svg?branch=master)](https://travis-ci.org/perftools/xhgui)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/perftools/xhgui/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/perftools/xhgui/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/perftools/xhgui/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/perftools/xhgui/?branch=master)

System Requirements
===================

XHGui has the following requirements:

 * PHP version 5.5 or later.
 * [XHProf](http://pecl.php.net/package/xhprof),
   [Uprofiler](https://github.com/FriendsOfPHP/uprofiler) or
   [Tideways](https://github.com/tideways/php-profiler-extension) to actually profile the data.
 * [MongoDB Extension](http://pecl.php.net/package/mongo) MongoDB PHP driver.
   XHGui requires verison 1.3.0 or later.
 * [MongoDB](http://www.mongodb.org/) MongoDB Itself. XHGui requires version 2.2.0 or later.
 * [mcrypt](http://php.net/manual/en/book.mcrypt.php) PHP must be configured
   with mcrypt (which is a dependency of Slim).
 * [dom](http://php.net/manual/en/book.dom.php) If you are running the tests
   you'll need the DOM extension (which is a dependency of PHPUnit).


Installation
============

1. Clone or download `xhgui` from Github.

2. Point your webserver to the `webroot` directory.

3. Set the permissions on the `cache` directory to allow the
   webserver to create files. If you're lazy, `0777` will work.

   The following command changes the permissions for the `cache` directory:

   ```
   chmod -R 0777 cache
   ```

4. Start a MongoDB instance. XHGui uses the MongoDB instance to store
   profiling data.

5. If your MongoDB setup uses authentication, or isn't running on the
   default port and localhost, update XHGui's `config/config.php` so that XHGui
   can connect to your `mongod` instance.

6. (**Optional**, but recommended) Add indexes to MongoDB to improve performance.

   XHGui stores profiling information in a `results` collection in the
   `xhprof` database in MongoDB. Adding indexes improves performance,
   letting you navigate pages more quickly.

   To add an index, open a `mongo` shell from your command prompt.
   Then, use MongoDB's `db.collection.ensureIndex()` method to add
   the indexes, as in the following:

   ```
   $ mongo
   > use xhprof
   > db.results.ensureIndex( { 'meta.SERVER.REQUEST_TIME' : -1 } )
   > db.results.ensureIndex( { 'profile.main().wt' : -1 } )
   > db.results.ensureIndex( { 'profile.main().mu' : -1 } )
   > db.results.ensureIndex( { 'profile.main().cpu' : -1 } )
   > db.results.ensureIndex( { 'meta.url' : 1 } )
   ```

7. Run XHGui's install script. The install script downloads composer and
   uses it to install the XHGui's dependencies.

   ```
   cd path/to/xhgui
   php install.php
   ```

8. Set up your webserver. The Configuration section below describes how
   to setup the rewrite rules for both nginx and apache.

Configuration
=============

Configure Webserver Re-Write Rules
----------------------------------

XHGui prefers to have URL rewriting enabled, but will work without it.
For Apache, you can do the following to enable URL rewriting:

1. Make sure that an .htaccess override is allowed and that AllowOverride
   has the directive FileInfo set for the correct DocumentRoot.

    Example configuration for Apache 2.4:
    ```
    <Directory /var/www/xhgui/>
        Options Indexes FollowSymLinks
        AllowOverride FileInfo
        Require all granted
    </Directory>
    ```
2. Make sure you are loading up mod_rewrite correctly.
   You should see something like:

    ```
    LoadModule rewrite_module libexec/apache2/mod_rewrite.so
    ```

3. XHGui comes with a `.htaccess` file to enable the remaining rewrite rules.

For nginx and fast-cgi, you can the following snippet as a start:

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


Configure XHGui Profiling Rate
-------------------------------

After installing XHGui, you may want to do change how frequently you
profile the host application. The `profiler.enable` configuration option
allows you to provide a callback function that specifies the requests that
are profiled. By default, XHGui profiles 1 in 100 requests.

The following example configures XHGui to only profile requests
from a specific URL path:

The following example configures XHGui to profile 1 in 100 requests,
excluding requests with the `/blog` URL path:

```php
// In config/config.php
return array(
    // Other config
    'profiler.enable' => function() {
        $url = $_SERVER['REQUEST_URI'];
        if (strpos($url, '/blog') === 0) {
            return false;
        }
        return rand(1, 100) === 42;
    }
);
```

In contrast, the following example configured XHGui to profile *every*
request:

```php
// In config/config.php
return array(
    // Other config
    'profiler.enable' => function() {
        return true;
    }
);
```


Configure 'Simple' URLs Creation
--------------------------------

XHGui generates 'simple' URLs for each profile collected. These URLs are
used to generate the aggregate data used on the URL view. Since
different applications have different requirements for how URLs map to
logical blocks of code, the `profile.simple_url` configuration option
allows you to provide specify the logic used to generate the simple URL.
By default, all numeric values in the query string are removed.

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


Profile an Application or Site
==============================

The simplest way to profile an application is to use
`external/header.php`. `external/header.php` is designed to be combined
with PHP's
[auto_prepend_file](http://www.php.net/manual/en/ini.core.php#ini.auto-pr
epend-file) directive. You can enable `auto_prepend_file` system-wide
through `php.ini`. Alternatively, you can enable `auto_prepend_file` per
virtual host.

With apache this would look like:

```
<VirtualHost *:80>
  php_admin_value auto_prepend_file "/Users/markstory/Sites/xhgui/external/header.php"
  DocumentRoot "/Users/markstory/Sites/awesome-thing/app/webroot/"
  ServerName site.localhost
</VirtualHost>
```
With Nginx in fastcgi mode you could use:

```
server {
  listen 80;
  server_name site.localhost;
  root /Users/markstory/Sites/awesome-thing/app/webroot/;
  fastcgi_param PHP_VALUE "auto_prepend_file=/Users/markstory/Sites/xhgui/external/header.php";
}
```

Profile a CLI Script
====================

The simplest way to profile a CLI is to use
`external/header.php`. `external/header.php` is designed to be combined with PHP's
[auto_prepend_file](http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file)
directive. You can enable `auto_prepend_file` system-wide
through `php.ini`. Alternatively,
you can enable include the `header.php` at the top of your script:

```php
<?php
require '/path/to/xhgui/external/header.php';
// Rest of script.
```

You can alternatively use the `-d` flag when running php:

```
php -d auto_prepend_file=/path/to/xhgui/external/header.php do_work.php
```

Saving & Importing Profiles
---------------------------

If your site cannot directly connect to your MongoDB instance, you can choose
to save your data to a temporary file for a later import to XHGui's MongoDB
database.

To configure XHGui to save your data to a temporary file,
change the `save.handler` setting to `file` and define your file's
path with `save.handler.filename`.

To import a saved file to MongoDB use XHGui's provided
`external/import.php` script.

Be aware of file locking: depending on your workload, you may need to
change the `save.handler.filename` file path to avoid file locking
during the import.

The following demonstrate the use of `external/import.php`:

```
php external/import.php -f /path/to/file
```

**Warning**: Importing the same file twice will load twice the run datas inside
MongoDB, resulting in duplicate profiles


Limiting MongoDB Disk Usage
---------------------------

Disk usage can grow quickly, especially when profiling applications with large
code bases or that use larger frameworks.

To keep the growth
in check, configure MongoDB to automatically delete profiling documents once they
have reached a certain age by creating a [TTL index](http://docs.mongodb.org/manual/core/index-ttl/).

Decide on a maximum profile document age in seconds: you
may wish to choose a lower value in development (where you profile everything),
than production (where you profile only a selection of documents). The
following command instructs Mongo to delete documents over 5 days (432000
seconds) old.

```
$ mongo
> use xhprof
> db.results.ensureIndex( { "meta.request_ts" : 1 }, { expireAfterSeconds : 432000 } )
```

Waterfall Display
-----------------

The goal of XHGui's waterfall display is to recognize that concurrent requests can
affect each other. Concurrent database requests, CPU-intensive
activities and even locks on session files can become relevant. With an
Ajax-heavy application, understanding the page build is far more complex than
a single load: hopefully the waterfall can help. Remember, if you're only
profiling a sample of requests, the waterfall fills you with impolite lies.

Some Notes:

 * There should probably be more indexes on MongoDB for this to be performant.
 * The waterfall display introduces storage of a new `request_ts_micro` value, as second level
   granularity doesn't work well with waterfalls.
 * The waterfall display is still very much in alpha.
 * Feedback and pull requests are welcome :)

Releases / Changelog
====================

See the [releases](https://github.com/preinheimer/xhgui/releases) for changelogs,
and release information.

License
=======

Copyright (c) 2013 Mark Story & Paul Reinheimer

Permission is hereby granted, free of charge, to any person obtaining a
copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
