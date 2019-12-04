<?php
/* Things you may want to tweak in here:
 *  - xhprof_enable() uses a few constants.
 *  - The values passed to rand() determine the the odds of any particular run being profiled.
 *  - The MongoDB collection and such.
 *
 * I use unsafe writes by default, let's not slow down requests any more than I need to. As a result you will
 * indubidubly want to ensure that writes are actually working.
 *
 * The easiest way to get going is to either include this file in your index.php script, or use php.ini's
 * auto_prepend_file directive http://php.net/manual/en/ini.core.php#ini.auto-prepend-file
 */

/* xhprof_enable()
 * See: http://php.net/manual/en/xhprof.constants.php
 *
 *
 * XHPROF_FLAGS_NO_BUILTINS
 *  Omit built in functions from return
 *  This can be useful to simplify the output, but there's some value in seeing that you've called strpos() 2000 times
 *  (disabled on PHP 5.5+ as it causes a segfault)
 *
 * XHPROF_FLAGS_CPU
 *  Include CPU profiling information in output
 *
 * XHPROF_FLAGS_MEMORY (integer)
 *  Include Memory profiling information in output
 *
 *
 * Use bitwise operators to combine, so XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY to profile CPU and Memory
 *
 */

/* uprofiler support
 * The uprofiler extension is a fork of xhprof.  See: https://github.com/FriendsOfPHP/uprofiler
 *
 * The two extensions are very similar, and this script will use the uprofiler extension if it is loaded,
 * or the xhprof extension if not.  At least one of these extensions must be present.
 *
 * The UPROFILER_* constants mirror the XHPROF_* ones exactly, with one additional constant available:
 *
 * UPROFILER_FLAGS_FUNCTION_INFO (integer)
 *  Adds more information about function calls (this information is not currently used by XHGui)
 */

/* Tideways XHProf support
 * The tideways_xhprof extension is a fork of xhprof. See https://github.com/tideways/php-profiler-extension
 *
 * It works on PHP 5.5+ and PHP 7 and improves on the ancient timing algorithms used by XHProf using
 * more modern Linux APIs to collect high performance timing data.
 *
 * v4 (tideways): The TIDEWAYS_* constants are similar to the ones by XHProf, however you need to disable timeline
 * mode when using XHGui, because it only supports callgraphs and we can save the overhead. Use
 * TIDEWAYS_FLAGS_NO_SPANS to disable timeline mode.
 *
 * v5 (tideways_xhprof): The TIDEWAYS_XHPROF_* constants are similar to the ones by XHProf, however you cannot use
 * additional TIDEWAYS_XHPROF_FLAGS_MEMORY_* flags since XHGui does not support the extra data that they produce.
 */

// this file should not - under no circumstances - interfere with any other application
if (!extension_loaded('xhprof')
    && !extension_loaded('uprofiler')
    && !extension_loaded('tideways')
    && !extension_loaded('tideways_xhprof')
) {
    error_log('xhgui - either extension xhprof, uprofiler, tideways or tideways_xhprof must be loaded');
    return;
}

// Use the callbacks defined in the configuration file
// to determine whether or not XHgui should enable profiling.
//
// Only load the config class so we don't pollute the host application's
// autoloaders.
$dir = dirname(__DIR__);
require_once $dir . '/src/Xhgui/Config.php';
$configDir = defined('XHGUI_CONFIG_DIR') ? XHGUI_CONFIG_DIR : $dir . '/config/';
if (file_exists($configDir . 'config.php')) {
    Xhgui_Config::load($configDir . 'config.php');
} else {
    Xhgui_Config::load($configDir . 'config.default.php');
}
unset($dir, $configDir);

if ((!extension_loaded('mongo') && !extension_loaded('mongodb')) && Xhgui_Config::read('save.handler') === 'mongodb') {
    error_log('xhgui - extension mongo not loaded');
    return;
}

if (!Xhgui_Config::shouldRun()) {
    return;
}

if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}

$skipBuiltIn = Xhgui_Config::read('profiler.skip_built_in');
$options = Xhgui_Config::read('profiler.options');

if (extension_loaded('uprofiler')) {
    $flags = UPROFILER_FLAGS_CPU | UPROFILER_FLAGS_MEMORY;

    if ($skipBuiltIn) {
        $flags |= UPROFILER_FLAGS_NO_BUILTINS;
    }

    uprofiler_enable($flags, $options);
} else if (extension_loaded('tideways')) {
    $flags = TIDEWAYS_FLAGS_CPU | TIDEWAYS_FLAGS_MEMORY | TIDEWAYS_FLAGS_NO_SPANS;

    if ($skipBuiltIn) {
        $flags |= TIDEWAYS_FLAGS_NO_BUILTINS;
    }

    tideways_enable($flags, $options);
} elseif (extension_loaded('tideways_xhprof')) {
    $flags = TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY;

    if ($skipBuiltIn) {
        $flags |= TIDEWAYS_XHPROF_FLAGS_NO_BUILTINS;
    }

    tideways_xhprof_enable($flags);
} else {
    $flags = XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY;

    $isFaulted = (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 4);
    if ($skipBuiltIn || $isFaulted) {
        $flags |= XHPROF_FLAGS_NO_BUILTINS;
    }

    xhprof_enable($flags, $options);
}

register_shutdown_function(
    function () {
        if (extension_loaded('uprofiler')) {
            $data['profile'] = uprofiler_disable();
        } else if (extension_loaded('tideways')) {
            $data['profile'] = tideways_disable();
        } elseif (extension_loaded('tideways_xhprof')) {
            $data['profile'] = tideways_xhprof_disable();
        } else {
            $data['profile'] = xhprof_disable();
        }

        // ignore_user_abort(true) allows your PHP script to continue executing, even if the user has terminated their request.
        // Further Reading: http://blog.preinheimer.com/index.php?/archives/248-When-does-a-user-abort.html
        // flush() asks PHP to send any data remaining in the output buffers. This is normally done when the script completes, but
        // since we're delaying that a bit by dealing with the xhprof stuff, we'll do it now to avoid making the user wait.
        ignore_user_abort(true);
        if (function_exists('session_write_close')) {
            session_write_close();
        }
        flush();

        if (!defined('XHGUI_ROOT_DIR')) {
            require dirname(__DIR__) . '/src/bootstrap.php';
        }

        if (Xhgui_Config::read('fastcgi_finish_request') && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $uri = array_key_exists('REQUEST_URI', $_SERVER)
            ? $_SERVER['REQUEST_URI']
            : null;
        if (empty($uri) && isset($_SERVER['argv'])) {
            $cmd = basename($_SERVER['argv'][0]);
            $uri = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        }
        
        $replace_url = Xhgui_Config::read('profiler.replace_url');
        if (is_callable($replace_url)) {
            $uri = $replace_url($uri);
        }

        $time = array_key_exists('REQUEST_TIME', $_SERVER)
            ? $_SERVER['REQUEST_TIME']
            : time();

        // In some cases there is comma instead of dot
        $delimiter = (strpos($_SERVER['REQUEST_TIME_FLOAT'], ',') !== false) ? ',' : '.';
        $requestTimeFloat = explode($delimiter, $_SERVER['REQUEST_TIME_FLOAT']);
        if (!isset($requestTimeFloat[1])) {
            $requestTimeFloat[1] = 0;
        }

        $requestTs = array('sec' => $time, 'usec' => 0);
        $requestTsMicro = array('sec' => $requestTimeFloat[0], 'usec' => $requestTimeFloat[1]);

        $data['meta'] = array(
            'url' => $uri,
            'SERVER' => $_SERVER,
            'get' => $_GET,
            'env' => $_ENV,
            'simple_url' => Xhgui_Util::simpleUrl($uri),
            'request_ts' => $requestTs,
            'request_ts_micro' => $requestTsMicro,
            'request_date' => date('Y-m-d', $time),
        );

        try {
            $config = Xhgui_Config::all();
            $config += array('db.options' => array());
            $saver = Xhgui_Saver::factory($config);
            $saver->save($data);
        } catch (Exception $e) {
            error_log('xhgui - ' . $e->getMessage());
        }
    }
);
