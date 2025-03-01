# Stand With Ukraine 🇺🇦

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://vshymanskyy.github.io/StandWithUkraine/)

---

# devium/processes

![Build status](https://img.shields.io/github/actions/workflow/status/vanodevium/processes/ci.yaml)
![Latest Version](https://img.shields.io/packagist/v/devium/processes)
![License](https://img.shields.io/packagist/l/devium/processes)
![Downloads](https://img.shields.io/packagist/dt/devium/processes)

This package is used to get a list of running processes on Windows or Unix-like systems, even Darwin.

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
$exists = $processes->exists($pid); // returns true of false
$arrayOfProcesses = $processes->get(); // returns array of processes where key is PID

// for rescanning processes, call rescan() method
$newArrayOfProcesses = $processes->rescan()->get();

// if you only need an array of processes, just use the static asArray():
$listOfProcesses = Processes::asArray();
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

**devium/processes** is open-sourced software licensed under the [MIT license](./LICENSE.md).

[Vano Devium](https://github.com/vanodevium/)

---

Made with ❤️ in Ukraine
