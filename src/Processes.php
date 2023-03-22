<?php

namespace Devium\Processes;

use ArrayAccess;
use Countable;
use Devium\Processes\Exceptions\LooksLikeBusyBoxException;
use Devium\Processes\Exceptions\PIDCanOnlyBeAnIntegerException;
use Devium\Processes\Exceptions\SkipUnixOneCallException;
use Symfony\Component\Process\Process;
use Throwable;

use function count;

/**
 * @implements ArrayAccess<int, array>
 */
class Processes implements ArrayAccess, Countable
{
    public const BUSY_BOX_MATCHES_COUNT = 7;
    public const ONE_CALL_MATCHES_COUNT = 13;
    public const MULTI_CALL_MATCHES_COUNT = 2;

    public const LINUX = 'linux';
    public const WINDOWS = 'windows';
    public const DARWIN = 'darwin';

    public const EMPTY_RESULT = 'empty';
    public const UNIX_RESULT = 'unix';
    public const BUSY_BOX_RESULT = 'busybox';
    public const WINDOWS_RESULT = 'windows';

    public const PID = 'pid';
    public const PPID = 'ppid';
    public const NAME = 'name';
    public const CMD = 'cmd';
    public const UID = 'uid';
    public const CPU = 'cpu';
    public const CPU_P = '%cpu';
    public const MEM_P = '%mem';
    public const MEMORY = 'memory';
    public const COMM = 'comm';
    public const COMM_MAC = 'COMM';
    public const ARGS = 'args';
    public const ARGS_MAC = 'ARGS';
    public const COMM_AND_ARGS = 'commAndArgs';
    public const COMMAND_TITLE = 'COMMAND';

    public const COLUMNS = [self::PID, self::PPID, self::UID, self::CPU_P, self::MEM_P, self::COMM, self::ARGS];
    public const REGEX = <<<REGEXP
/^\s*(?<pid>\d+)\s+(?<ppid>\d+)\s+(?<uid>\d+)\s+(?<cpu>\d+\.\d+)\s+(?<memory>\d+\.\d+)\s+(?<commAndArgs>.*)/
REGEXP;
    public const SHORT_REGEX = <<<REGEXP
/^\s*(?<pid>\d+)\s+(?<ppid>\d+)\s+(?<cmd>.*)/
REGEXP;

    /**
     * @var array<int|string, array<mixed>>
     */
    private $processes = [];

    /**
     * @var string
     */
    private $resultType = self::EMPTY_RESULT;

    /**
     * @var bool
     */
    private $all = false;

    /**
     * @var bool
     */
    private $multi = false;

    /**
     * @param bool $all
     * @param bool $multi
     */
    public function __construct(?bool $all = null, ?bool $multi = null)
    {
        $this->setScanOptions($all, $multi)->scan();
    }

    /**
     * @return Processes
     */
    public function rescan(): Processes
    {
        return $this->scan();
    }

    /**
     * @return array<int|string, array<mixed>>
     */
    public function get(): array
    {
        return $this->processes;
    }

    /**
     * @return array<int|string, array<mixed>>
     */
    public function toArray(): array
    {
        return $this->get();
    }

    /**
     * @param null|mixed $pid
     * @return bool
     * @throws PIDCanOnlyBeAnIntegerException
     */
    public function exists($pid = null): bool
    {
        if (null === $pid) {
            return false;
        }

        $this->validatePID($pid);

        return null !== $this->processes[$pid];
    }

    /**
     * @return string
     */
    public function getResultType(): string
    {
        return $this->resultType;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->processes[$offset]);
    }

    /**
     * @param mixed $offset
     * @return null|mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->processes[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->processes[] = $value;
        } else {
            $this->processes[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->processes[$offset]);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->processes);
    }

    /**
     * @param bool $all
     * @return string
     */
    protected function getFlags(bool $all = false): string
    {
        return ($all ? 'a' : '') . 'wwxo';
    }

    /**
     * @return string
     */
    private function detectOS(): string
    {
        switch (strtolower(PHP_OS_FAMILY)) {
            case self::DARWIN:
                return self::DARWIN;
            case self::WINDOWS:
                return self::WINDOWS;
            default:
                return self::LINUX;
        }
    }

    /**
     * @return Processes
     * @uses self::windows()
     * @uses self::linux()
     * @uses self::darwin()
     */
    private function scan(): Processes
    {
        $this->{$this->detectOS()}();
        return $this;
    }

    /**
     * @param mixed $pid
     * @throws PIDCanOnlyBeAnIntegerException
     */
    private function validatePID($pid): void
    {
        if (!is_int($pid)) {
            throw new PIDCanOnlyBeAnIntegerException();
        }
    }

    /**
     * @param bool|null $all
     * @param bool|null $multi
     * @return Processes
     */
    private function setScanOptions(?bool $all, ?bool $multi): Processes
    {
        $this->setAllProperty($all);
        $this->setMultiProperty($multi);

        return $this;
    }

    /**
     * @param bool|null $all
     * @return void
     */
    private function setAllProperty(?bool $all): void
    {
        $this->all = (bool)$all;
    }

    /**
     * @param bool|null $multi
     * @return void
     */
    private function setMultiProperty(?bool $multi): void
    {
        $this->multi = (bool)$multi;
    }

    /**
     * @return void
     */
    private function windows(): void
    {
        $processes = [];

        $process = new Process([__DIR__ . '/../fastlist.exe']);
        $process->run();
        $output = $process->getOutput();
        $output = explode(PHP_EOL, trim($output));
        $output = array_map(static function ($line) {
            return explode("\t", $line);
        }, $output);
        array_map(static function ($item) use (&$processes) {
            [$name, $pid, $ppid] = $item;
            $processes[(int)$pid] = [
                self::PID => (int)$pid,
                self::PPID => (int)$ppid,
                self::NAME => trim($name),
            ];
        }, $output);

        $this->setProcesses($processes, self::WINDOWS_RESULT);
    }

    /**
     * @return void
     */
    private function darwin(): void
    {
        $processes = [];

        $process = new Process(['ps', $this->getFlags($this->all), implode(',', self::COLUMNS)]);
        $process->run();

        $output = $process->getOutput();
        $output = explode(PHP_EOL, $output);
        $nameStart = strpos($output[0], self::COMM_MAC) ?: 0;
        $cmdStart = strpos($output[0], self::ARGS_MAC) ?: 0;
        $nameLen = $cmdStart - $nameStart - 1;
        array_shift($output);

        foreach ($output as $line) {
            preg_match(self::REGEX, $line, $matches);
            try {
                $this->fillProcessValues(
                    $processes,
                    (int)$matches[self::PID],
                    (int)$matches[self::PPID],
                    (int)$matches[self::UID],
                    (float)$matches[self::CPU],
                    (float)$matches[self::MEMORY],
                    substr($line, $nameStart, $nameLen),
                    substr($line, $cmdStart)
                );
            } catch (Throwable $e) {
            }
        }

        $this->setProcesses($processes, self::UNIX_RESULT);
    }

    /**
     * @return void
     */
    private function linux(): void
    {
        try {
            if ($this->multi === true) {
                throw new SkipUnixOneCallException();
            }
            $this->unixOneCall($this->all);
        } catch (Throwable $e) {
            try {
                if ($e instanceof LooksLikeBusyBoxException) {
                    throw $e;
                }
                $this->unixMultiCall($this->all);
            } catch (LooksLikeBusyBoxException $e) {
                $this->busyBoxCall();
            } catch (Throwable $e) {
            }
        }
    }

    /**
     * @return void
     */
    private function busyBoxCall(): void
    {
        $processes = [];

        $process = new Process(['ps', '-o', implode(',', [self::PID, self::PPID, self::ARGS])]);
        $process->run();

        $output = $process->getOutput();
        $output = explode(PHP_EOL, $output);
        array_shift($output);

        foreach ($output as $line) {
            preg_match(self::SHORT_REGEX, $line, $matches);
            if (self::BUSY_BOX_MATCHES_COUNT !== count($matches)) {
                continue;
            }
            try {
                $pid = (int)$matches[self::PID];
                $this->fillProcessValues($processes, $pid, (int)$matches[self::PPID], 0, 0, 0, $matches[self::CMD], '');
            } catch (Throwable $e) {
            }
        }

        $this->setProcesses($processes, self::BUSY_BOX_RESULT);
    }

    /**
     * @param bool $all
     * @return void
     * @throws LooksLikeBusyBoxException
     */
    private function unixOneCall(bool $all = false): void
    {
        $processes = [];

        $process = new Process(['ps', $this->getFlags($all), implode(',', self::COLUMNS)]);
        $process->run();

        $output = $process->getOutput();
        $output = explode(PHP_EOL, $output);
        $columns = $this->split($output[0]);
        if (count($columns) < count(self::COLUMNS)) {
            throw new LooksLikeBusyBoxException();
        }
        $startIndex = strpos($output[0], self::COMMAND_TITLE);
        $endIndex = strpos($output[0], self::COMMAND_TITLE, $startIndex + 1);
        $splitIndex = $endIndex - $startIndex;
        array_shift($output);

        foreach ($output as $line) {
            preg_match(self::REGEX, $line, $matches);
            if (self::ONE_CALL_MATCHES_COUNT !== count($matches)) {
                continue;
            }
            try {
                $pid = (int)$matches[self::PID];
                $name = substr($matches[self::COMM_AND_ARGS], 0, $splitIndex);
                $cmd = substr($matches[self::COMM_AND_ARGS], $splitIndex);
                $this->fillProcessValues(
                    $processes,
                    $pid,
                    (int)$matches[self::PPID],
                    (int)$matches[self::UID],
                    (float)$matches[self::CPU],
                    (float)$matches[self::MEMORY],
                    $name,
                    $cmd
                );
            } catch (Throwable $e) {
            }
        }

        $this->setProcesses($processes, self::UNIX_RESULT);
    }

    /**
     * @param bool $all
     * @return void
     * @throws LooksLikeBusyBoxException
     */
    private function unixMultiCall(bool $all = false): void
    {
        $processes = [];

        foreach (self::COLUMNS as $cmd) {
            if (self::PID === $cmd) {
                continue;
            }
            $process = new Process(['ps', $this->getFlags($all), self::PID . ",$cmd"]);
            $process->run();

            $output = $process->getOutput();
            $output = explode(PHP_EOL, $output);
            array_shift($output);

            foreach ($output as $line) {
                $line = trim($line);
                if (!$line) {
                    continue;
                }
                $split = $this->split($line);
                if (self::MULTI_CALL_MATCHES_COUNT > count($split)) {
                    throw new LooksLikeBusyBoxException();
                }
                $pid = (int)array_shift($split);
                $val = trim(implode(' ', $split));

                if (!isset($processes[$pid])) {
                    $this->fillProcessValues($processes, $pid, -1, -1, 0, 0, '', '');
                }

                $this->castValues($processes, $cmd, $pid, $val);
            }
        }

        $this->setProcesses($processes, self::UNIX_RESULT);
    }

    /**
     * @param array<int, array<mixed>> $processes
     * @param string $type
     * @return void
     */
    private function setProcesses(array $processes, string $type): void
    {
        $this->processes = $processes;
        $this->resultType = $type;
    }

    /**
     * @param array<int, array<mixed>> $processes
     * @param int $pid
     * @param int $ppid
     * @param int $uid
     * @param float $cpu
     * @param float $memory
     * @param string $name
     * @param string $command
     */
    private function fillProcessValues(
        array &$processes,
        int $pid,
        int $ppid,
        int $uid,
        float $cpu,
        float $memory,
        string $name,
        string $command
    ): void {
        $processes[$pid] = [
            self::PID => $pid,
            self::PPID => $ppid,
            self::UID => $uid,
            self::CPU => $cpu,
            self::MEMORY => $memory,
            self::NAME => trim($name),
            self::CMD => trim($command),
        ];
    }

    /**
     * @param array<int, array<mixed>> $processes
     * @param string $cmd
     * @param int $pid
     * @param mixed $val
     */
    private function castValues(array &$processes, string $cmd, int $pid, $val): void
    {
        switch ($cmd) {
            case self::CPU_P:
                $processes[$pid][self::CPU] = (float)$val;
                break;
            case self::MEM_P:
                $processes[$pid][self::MEMORY] = (float)$val;
                break;
            case self::PPID:
                $processes[$pid][self::PPID] = (int)$val;
                break;
            case self::UID:
                $processes[$pid][self::UID] = (int)$val;
                break;
            case self::COMM:
                $processes[$pid][self::NAME] = trim($val);
                break;
            case self::ARGS:
                $processes[$pid][self::CMD] = trim($val);
                break;
        }
    }

    /**
     * @param mixed $line
     * @return string[]
     */
    private function split($line): array
    {
        return array_filter(explode(' ', $line), static function ($item) {
            return $item !== '';
        });
    }
}
