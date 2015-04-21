<?php

/* The easiest way to get going is to either include this file in your index.php script, or use php.ini's
 * auto_prepend_file directive http://php.net/manual/en/ini.core.php#ini.auto-prepend-file
 */

require_once dirname(__DIR__) . '/src/Xhgui/Profiler.php';

$profiler = new Xhgui_Profiler();

$profiler->startProfiling();

register_shutdown_function(array($profiler, 'finishProfiling'));
