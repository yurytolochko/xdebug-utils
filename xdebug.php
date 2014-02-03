<?php
set_time_limit(0);

include_once(__DIR__.'/utils/Command.php');
include_once(__DIR__.'/utils/CommandDispatcher.php');
include_once(__DIR__.'/utils/CacheGrind.php');
include_once(__DIR__.'/utils/Getopt.php');
include_once(__DIR__.'/commands/Profiler.php');


$dispatcher = new CommandDispatcher($argv, 'profiler');
$dispatcher->dispatch();