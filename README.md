# Devium\Processes

![Build status](https://img.shields.io/travis/vanodevium/processes/master)
![Latest Version](https://img.shields.io/packagist/v/devium/processes)
![License](https://img.shields.io/packagist/l/devium/processes)
![Downloads](https://img.shields.io/packagist/dt/devium/processes)

This package used for getting list of running processes on Windows or Unix-like systems

> Thanks to [fastlist](https://github.com/MarkTiedemann/fastlist) for processes on Windows.

## Installation

```sh
composer require devium/processes
```

## Usage

```php
use Devium\Processes\Processes;

// some PID, integer
$pid = 1234;
// get all processes except both session leaders, default false
$all = true;

$processes = new Processes($all);
$exists = $processes->exists($pid); // return true of false
$arrayOfProcesses = $processes->get(); // return array of processes where key is PID

// for rescanning processes, call rescan() method
$newArrayOfProcesses = $processes->rescan()->get();
```

## Structure of processes array

#### For windows
```json
{
  "PID": {
    "pid": "integer",
    "ppid": "integer",
    "name": "string"
  }
}
```

#### For unix-like systems
```json
{
  "PID": {
    "pid": "integer",
    "ppid": "integer",
    "name": "string",
    "uid": "integer",
    "cpu": "float",
    "memory": "float",
    "cmd": "string"
  }
}
```

## Testing
```sh
composer test
```

## License

The Devium\Processes package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
