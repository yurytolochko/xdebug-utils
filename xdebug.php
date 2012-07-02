<?php
set_time_limit(0);

include_once('./utils/Command.php');
include_once('./utils/CommandDispatcher.php');
include_once('./utils/CacheGrind.php');
include_once('./utils/Getopt.php');

include_once('./commands/Profiler.php');


$dispatcher = new CommandDispatcher($argv);
$dispatcher->dispatch();