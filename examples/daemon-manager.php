<?php

namespace PE\Component\Cronos\Process;

require_once __DIR__ . '/../vendor/autoload.php';

$commands = [
    'command1' => function (WorkerProcessInterface $thread) {
        $i = 0;
        while ($i < 1000000000) {
            if ($thread->isShouldTerminate()) {
                return;//This need for faster exit
            }
            $i++;
        }
    },
    'command2' => function () { usleep(mt_rand(10, 20) * 1000000); },
    'command3' => function () { usleep(mt_rand(10, 20) * 1000000); },
];

$factory = new Factory();

$master = new MasterProcess($factory);
$master->setTitle($title = sprintf('daemon-manager(%s): master process', 'instance'));
echo $title . "\n";

while (!$master->isShouldTerminate()) {
    foreach ($commands as $command => $callable) {
        if (count($master->getChildren($command)) > 0) {
            continue;
        }

        $worker = $master->fork(function (WorkerProcessInterface $thread) use ($command, $callable) {
            $thread->setTitle($title = sprintf('daemon-manager(%s): worker process(%s)', 'instance', $command));
            echo $title . "\n";
            $callable($thread);
        });

        $worker->setAlias($command);

        usleep(1000000);
    }
}

$master->wait();
