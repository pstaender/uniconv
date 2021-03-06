<?php

declare(strict_types=1);
require_once(__DIR__ . '/config/config.php');


if (php_sapi_name() !== 'cli') {
    echo "Error: This is script is only to be run in cli";
    exit(1);
}
$jobFiles = glob(__DIR__ . '/jobs/*.json');

usort($jobFiles, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});


$workerPool = new \QXS\WorkerPool\WorkerPool();
$workerPool->setWorkerPoolSize(4)
    ->create(
        new \QXS\WorkerPool\ClosureWorker(
        /**
         * @param mixed $input the input from the WorkerPool::run() Method
         * @param \QXS\WorkerPool\Semaphore $semaphore the semaphore to synchronize calls accross all workers
         * @param \ArrayObject $storage a persistent storage for the current child process
         */
            function ($jobFile, $semaphore, $storage) {
                $pid = getmypid();
                $pidFile = $jobFile . '.pid';
                if (file_exists($pidFile)) {
                    echo "Skipping '$jobFile', see pid #" . trim(file_get_contents($pidFile));
                    return null;
                }
                file_put_contents($pidFile, $pid);
                try {
                    $data = json_decode(file_get_contents($jobFile), true);
                    $fromExtension = strtolower($data['file']['extension']);
                    $toExtension = strtolower($data['target']);

                    $converter = App\Converter::create(
                        fromExtension: $fromExtension,
                        toExtension: $toExtension,
                        options: $data['options']
                    );

                    $shellScriptCommands = App\Helper::conversionShellScript($converter, $fromExtension, $toExtension);

                    // this chmod 777 is a poor workaround for dockers permission problems...
                    // otherwise containers using a user (gid 1000 for example) will run into permission problems
                    shell_exec("chmod -R 777 '".realpath(App\Helper::conversionFolder($data['user'], $data['id']))."'");

                    $commands = App\Converter::commands(
                        App\Helper::conversionFolder($data['user'], $data['id']),
                        $fromExtension,
                        $toExtension,
                        $converter,
                        $shellScriptCommands
                    );

                    foreach ($commands as $cmd) {
                        echo "\n\t$ $cmd";
                        echo "\n" . shell_exec($cmd);
                    }

                    unlink($pidFile);
                    rename($jobFile, $jobFile . '.done');
                } catch (Exception $e) {
                    $whoops = new \Whoops\Run;
                    $whoops->allowQuit(false);
                    $whoops->writeToOutput(true);
                    $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler);
                    $whoops->handleException($e);
                    echo $e->getMessage();

                    $exceptionLogfile = App\Helper::conversionFolder($data['user'], $data['id']) . '/exception.log';
                    file_put_contents($exceptionLogfile, $e->getMessage());

                    unlink($pidFile);
                    rename($jobFile, $jobFile . '.failed');
                }

                return null;
            }
        )
    );

foreach ($jobFiles as $jobFile) {
    $workerPool->run($jobFile);
}

$workerPool->waitForAllWorkers();

echo '- no jobs in queue -';