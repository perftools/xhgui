[![Build Status](https://scrutinizer-ci.com/g/lauripiisang/xhgui-data-collector/badges/build.png?b=master)](https://scrutinizer-ci.com/g/lauripiisang/xhgui-data-collector/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lauripiisang/xhgui-data-collector/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lauripiisang/xhgui-data-collector/?branch=master)

# XHGUI Data Collector

This is a small standalone module which you can use to collect and store
[XHProf][1] performance data for later usage in [XHGUI][2].

## Goals
 - Compatibility with PHP >= 5.3.0
 - No dependencies aside from the relevant extensions
 - Customizable and configurable so you can build your own logic on top of it

System Requirements
===================

For using the data collection classes you will need the following:

 * PHP version 5.3 or later.
 * [XHProf](http://pecl.php.net/package/xhprof),
   [Uprofiler](https://github.com/FriendsOfPHP/uprofiler) or
   [Tideways](https://github.com/tideways/php-profiler-extension) to actually profile the data.
 * Some way to access a [MongoDB][3] server. Choose either:
    * [MongoDB Extension](http://pecl.php.net/package/mongo)>=1.3.0 (MongoDB PHP driver from pecl)
    * `alcaeus/mongo-php-adapter` composer dependency.   
 * a MongoDB server. XHGUI requires version 2.2.0 or later.

 When in doubt, refer to [XHGUI][2] repository's composer.json or this repository's composer.json `suggests` section.
 
 [1]:https://pecl.php.net/package/xhprof
 [2]:https://github.com/perftools/xhgui
 [3]:http://www.mongodb.org/
