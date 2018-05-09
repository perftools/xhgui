[![Build Status](https://scrutinizer-ci.com/g/perftools/xhgui-collector/badges/build.png?b=master)](https://scrutinizer-ci.com/g/perftools/xhgui-collector/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/perftools/xhgui-collector/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/perftools/xhgui-collector/?branch=master)

# XHGUI Collector

This is a small standalone module which you can use to collect and store
[XHProf][1] performance data for later usage in [XHGUI][2].

## Goals
 - Compatibility with PHP >= 5.3.0
 - No dependencies aside from the relevant extensions
 - Customizable and configurable so you can build your own logic on top of it

## XHGUI Compatibility

This project was originally forked from [perftools/xhgui@133051f], which was after the tag 0.7.1.

This should ensure compatibility for most tags up to 0.7.1 (included).

The only change that would break compatibility would be a schema change on the XHGUI side.

This table represents current known information about compatibility between this project and [XHGUI][2] data schema.

| XHGUI Collector version | XHGUI Version | Compatibility                           |
|-------------------------|---------------|-----------------------------------------|
| 1.0.0 - 1.x             | 0.2.0 - 0.9.0 | presumed compatible - no schema changes |

## Usage

### Profile an Application or Site

The simplest way to profile an application is to use `external/header.php`.
`external/header.php` is designed to be combined with PHP's
[auto_prepend_file][4] directive. You can enable `auto_prepend_file` system-wide
through `php.ini`. Alternatively, you can enable `auto_prepend_file` per virtual
host.

With apache this would look like:

```apache
<VirtualHost *:80>
  php_admin_value auto_prepend_file "/Users/markstory/Sites/xhgui/external/header.php"
  DocumentRoot "/Users/markstory/Sites/awesome-thing/app/webroot/"
  ServerName site.localhost
</VirtualHost>
```
With Nginx in fastcgi mode you could use:

```nginx
server {
  listen 80;
  server_name site.localhost;
  root /Users/markstory/Sites/awesome-thing/app/webroot/;
  fastcgi_param PHP_VALUE "auto_prepend_file=/Users/markstory/Sites/xhgui/external/header.php";
}
```

### Profile a CLI Script

The simplest way to profile a CLI is to use `external/header.php`.
`external/header.php` is designed to be combined with PHP's
[auto_prepend_file][4] directive. You can enable `auto_prepend_file` system-wide
through `php.ini`. Alternatively, you can enable include the `header.php` at the
top of your script:

```php
<?php
require '/path/to/xhgui/external/header.php';
// Rest of script.
```

You can alternatively use the `-d` flag when running php:

```bash
php -d auto_prepend_file=/path/to/xhgui/external/header.php do_work.php
```

### Use with environment variables

* run `composer require perftools/xhgui-collector` 
* include these lines into your bootstrap file (e.g. index.php) 

```
define('XHGUI_CONFIG_DIR', PATH_TO_OWN_CONFIG);
require_once PATH_TO_YOUR_VENDOR . '/perftools/xhgui-collector/external/header.php';
```
 
* set environment variables to configure the mongodb host, database name and more

| env | description | example | default |
| ---- | ----------- | ------- | ------- |
| `XHGUI_MONGO_URI` | the host and port to the mongo db | `XHGUI_MONGO_URI=mongo:27017` | 127.0.0.1:27017 |
| `XHGUI_MONGO_DB` | the database name for the profiling data | `XHGUI_MONGO_DB=xhprof` | xhprof |
| `XHGUI_PROFILING_RATIO` | the ratio of profiled requests | `XHGUI_PROFILING_RATIO=50` which profiles 50% of all requests | `XHGUI_PROFILING_RATIO=100` |
| `XHGUI_PROFILING` | if this env var is set with any value the profiling is enabled | `XHGUI_PROFILING=enabled` | it is not set per default, so no profiling will be triggered |


## System Requirements

For using the data collection classes you will need the following:

 * PHP version 5.3 or later.
 * [XHProf](http://pecl.php.net/package/xhprof),
   [Uprofiler](https://github.com/FriendsOfPHP/uprofiler) or
   [Tideways](https://github.com/tideways/php-profiler-extension) to actually profile the data.
 * Some way to access a [MongoDB][3] server. Choose either:
    * [MongoDB Extension](http://pecl.php.net/package/mongo)>=1.3.0 (MongoDB PHP driver from pecl)
    * `alcaeus/mongo-php-adapter` composer dependency.   
 * a MongoDB server. XHGUI requires version 2.2.0 or later.

When in doubt, refer to [XHGUI][2] repository's composer.json or this
repository's composer.json `suggests` section.


 [1]:https://pecl.php.net/package/xhprof
 [2]:https://github.com/perftools/xhgui
 [3]:http://www.mongodb.org/
 [perftools/xhgui@133051f]:https://github.com/perftools/xhgui/commit/133051f0c27240adadf00eadc236be595caadcdd
 [4]:http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file
