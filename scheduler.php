<?php
require"vendor/autoload.php";
use GO\Scheduler;

$scheduler = new Scheduler();
$scheduler->php('hello.php')
    ->at('* * * * *') // run every minutes
->output("cron.log"); // save log to file
$scheduler->run();