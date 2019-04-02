## Requirements

The following versions of PHP are supported.

* PHP 7.1+

## Installation

To install, use composer:

```
php composer.phar require pe/component-cronos-process
```

## Process forking usage example

```php
<?php

namespace PE\Component\Cronos;

use PE\Component\Cronos\Process\Process;

$master = new Process();

// Start loop until signal received
while (!$master->isShouldTerminate()) {
    $master->dispatch();
    
    for ($i = 0; $i < 4; $i++) {
        $worker = new Process();
        $worker->setCallable(function (Process $process) {
            // Do some stuff...
        });
        
        $master->fork($worker);
    }
}

// Wait for children exited
$master->wait();
```

## Daemon usage example

```php
<?php

namespace PE\Component\Cronos;

use PE\Component\Cronos\Process\Daemon;
use PE\Component\Cronos\Process\Process;

// Create background process
$process = new Process();
$process->setCallable(function (Process $process) {
    while (!$process->isShouldTerminate()) {
        $process->dispatch();
        // Do some stuff...
    }
});

// Run background process as a daemon
$daemon = new Daemon('path/to/pid.file');
$daemon->fork($process);

// Stop daemon, this send SIGTERM to background process
$daemon = new Daemon('path/to/pid.file');
$daemon->kill();
```
