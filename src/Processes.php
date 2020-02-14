<?php

namespace Devium\Processes;

use Symfony\Component\Process\Process;
use Throwable;
use function count;
use const DIRECTORY_SEPARATOR;

class Processes
{

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
    public const ARGS = 'args';

    public const COLUMNS = [self::PID, self::PPID, self::UID, self::CPU_P, self::MEM_P, self::COMM, self::ARGS];
    public const REGEX = '/^[\s]?(\d+)[\s]+(\d+)[\s]+(\d+)[\s]+(\d+\.\d+)[\s]+(\d+\.\d+)[\s]+([\S]+(?:\s+<defunct>)?)[\s]+(.*)/';

    /**
     * @var mixed[]
     */
    private $processes = [];

    /**
     * @param bool $all
     */
    public function __construct(bool $all = false)
    {
        $this->scan($all);
    }

    /**
     * @param bool $all
     * @return Processes
     */
    public function scan(bool $all = false): Processes
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->windows();
        } else {
            $this->unix($all);
        }

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function get(): array
    {
        return $this->processes;
    }

    /**
     * @param int $pid
     * @return bool
     */
    public function exists(int $pid): bool
    {
        return isset($this->get()[$pid]);
    }

    /**
     * @return mixed[]
     */
    private function windows(): array
    {
        $processes = [];

        /**
         * Fastlist source code
         * @link https://github.com/MarkTiedemann/fastlist
         */
        $process = new Process([__DIR__ . '/../fastlist.exe']);
        $process->run();
        $output = $process->getOutput();
        $output = explode("\n", trim($output));
        $output = array_map(static function ($line) {
            return explode("\t", $line);
        }, $output);
        array_map(static function ($item) use (&$processes) {
            [$name, $pid, $ppid] = $item;
            $processes[(int)$pid] = [
                self::PID => (int)$pid,
                self::PPID => (int)$ppid,
                self::NAME => $name,
            ];
        }, $output);

        $this->processes = $processes;
        return $this->processes;
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
     * @param bool $all
     * @return mixed[]
     */
    private function unix(bool $all = false): array
    {
        try {
            try {
                return $this->unixOneCall($all);
            } catch (Throwable $e) {
                return $this->unixMultiCall($all);
            }
        } catch (Throwable $e) {
            return $this->processes;
        }
    }

    /**
     * @param bool $all
     * @return mixed[]
     */
    private function unixOneCall(bool $all = false): array
    {
        $processes = [];

        $process = new Process(['ps', $this->getFlags($all), implode(',', self::COLUMNS)]);
        $process->run();

        $output = $process->getOutput();
        $output = explode("\n", $output);
        array_shift($output);

        foreach ($output as $line) {
            preg_match(self::REGEX, $line, $matches);
            if (count(self::COLUMNS) !== count($matches) - 1) {
                continue;
            }
            if (!isset($matches[1])) {
                continue;
            }
            try {
                $pid = (int)$matches[1];
                $processes[$pid] = [
                    self::PID => $pid,
                    self::PPID => (int)$matches[2],
                    self::UID => (int)$matches[3],
                    self::CPU => (float)$matches[4],
                    self::MEMORY => (float)$matches[5],
                    self::NAME => $matches[6],
                    self::CMD => $matches[7],
                ];
            } catch (Throwable $e) {

            }
        }

        $this->processes = $processes;
        return $this->processes;
    }

    /**
     * @param bool $all
     * @return mixed[]
     */
    private function unixMultiCall(bool $all = false): array
    {
        $processes = [];

        foreach (self::COLUMNS as $cmd) {
            if (self::PID === $cmd) {
                continue;
            }
            $process = new Process(['ps', $this->getFlags($all), self::PID . ",${cmd}"]);
            $process->run();

            $output = $process->getOutput();
            $output = explode("\n", $output);
            array_shift($output);

            foreach ($output as $line) {
                $line = trim($line);
                $split = array_filter(preg_split('/\s+/', $line) ?: []);
                if (2 !== count($split)) {
                    continue;
                }
                $pid = (int)$split[0];
                $val = trim($split[1]);

                if (!isset($processes[$pid])) {
                    $processes[$pid] = [
                        self::PID => $pid,
                        self::PPID => -1,
                        self::UID => -1,
                        self::CPU => 0.0,
                        self::MEMORY => 0.0,
                        self::NAME => '',
                        self::CMD => '',
                    ];
                }

                if (self::CPU_P === $cmd) {
                    $processes[$pid][self::CPU] = (float)$val;
                }

                if (self::MEM_P === $cmd) {
                    $processes[$pid][self::MEMORY] = (float)$val;
                }

                if (self::PPID === $cmd) {
                    $processes[$pid][self::PPID] = (int)$val;
                }

                if (self::UID === $cmd) {
                    $processes[$pid][self::UID] = (int)$val;
                }

                if (self::COMM === $cmd) {
                    $processes[$pid][self::NAME] = $val;
                }

                if (self::ARGS === $cmd) {
                    $processes[$pid][self::CMD] = $val;
                }
            }
        }

        $this->processes = $processes;
        return $this->processes;
    }
}
