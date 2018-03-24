<?php
require"vendor/autoload.php";

$climate = new League\CLImate\CLImate;
$climate->arguments->add(
    [
    "option" => ["description" => "available option: start|status|stop",
    "required" => false,
    "castTo" => "string"],
    "help" => [
    "prefix" => "h", "longPrefix" => "help", "description" => 'Prints a usage statement', 'noValue' => true]
    ]
);

$climate->description('php cron daemon by ospek project');
$climate->arguments->parse();

$lock = fopen('cron.pid', 'c+');

if (null != $climate->arguments->get('help') && null == $climate->arguments->get('option')) {
    $climate->lightYellow("please specific option to get help information");
} elseif (null != $climate->arguments->get('help') && trim($climate->arguments->get('option')) == "start") {
    $climate->lightGreen("php cron.php start");
    $climate->lightYellow("start: starting cron daemon");
} elseif (null != $climate->arguments->get('help') && trim($climate->arguments->get('option')) == "status") {
    $climate->lightGreen("php cron.php status");
    $climate->lightYellow("status: get cron daemon running status");
} elseif (null != $climate->arguments->get('help') && trim($climate->arguments->get('option')) == "stop") {
    $climate->lightGreen("php cron.php stop");
    $climate->lightYellow("stop: stop cron daemon from running");
} 

elseif (null != $climate->arguments->get('option') && trim($climate->arguments->get('option')) == "start") {
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        $climate->lightRed("cron already running!");
        exit(1);
    }
 
        switch ($pid = pcntl_fork()) {
    case -1:
        $climate->lightRed("unable to fork!");
        exit(1);
    case 0: // this is the child process
        break;
    default: // otherwise this is the parent process
        fseek($lock, 0);
        ftruncate($lock, 0);
        fwrite($lock, $pid);
        fflush($lock);
        exit;
        }
 
        if (posix_setsid() === -1) {
             $climate->lightRed("could not setsid!");
             exit(1);
        }
 
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
 
        $stdIn = fopen('/dev/null', 'r'); // set fd/0
        $stdOut = fopen('/dev/null', 'w'); // set fd/1
        $stdErr = fopen('php://stdout', 'w'); // a hack to duplicate fd/1 to 2
 
        pcntl_signal(SIGTSTP, SIG_IGN);
        pcntl_signal(SIGTTOU, SIG_IGN);
        pcntl_signal(SIGTTIN, SIG_IGN);
        pcntl_signal(SIGHUP, SIG_IGN);
 
        // cron like execute scheduler every minutes
        while (true) {
            sleep(60);
            exec('php scheduler.php > /dev/null 2>&1');
        }
} elseif (null != $climate->arguments->get('option') && trim($climate->arguments->get('option')) == "status") {
    (flock($lock, LOCK_EX | LOCK_NB))?$climate->lightYellow("status: stopped"):$climate->lightGreen("status: running");
} elseif (null != $climate->arguments->get('option') && trim($climate->arguments->get('option')) == "stop") {
    if (flock($lock, LOCK_EX | LOCK_NB)) {
        $climate->lightRed("cron not running");
        exit(1);
    }
    $pid = fgets($lock);
 
    posix_kill($pid, SIGTERM);
    $climate->lightGreen("cron stop successful");
    exit(0);
} else {
    $climate->usage();
}