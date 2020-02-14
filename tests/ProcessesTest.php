<?php

use PHPUnit\Framework\TestCase;
use Devium\Processes\Processes;
use Symfony\Component\Process\Process;

class ProcessesTest extends TestCase
{
    /** @var int */
    private $pid = 0;

    /**
     * @dataProvider processesArgumentsProvider
     * @param bool $all
     * @param bool $multi
     */
    public function testProcessesOnUnix(bool $all, bool $multi): void
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test run only on Unix');
        }

        $process = new Process(['tests/bin/while.exe']);

        $process->start();
        $this->pid = $process->getPid();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        $this->assertGreaterThan(0, $this->pid);

        $processes = new Processes($all, $multi);

        $this->assertFalse($processes->exists(null));
        $this->assertTrue($processes->exists($this->pid));

        $processInformation = $processes->get()[$this->pid];

        $this->assertArrayHasKey(Processes::PID, $processInformation);
        $this->assertIsInt($processInformation[Processes::PID]);
        $this->assertGreaterThan(0, $processInformation[Processes::PID], 'PID');
        $this->assertArrayHasKey(Processes::PPID, $processInformation);
        $this->assertIsInt($processInformation[Processes::PPID]);
        $this->assertGreaterThan(0, $processInformation[Processes::PPID], 'PPID');
        $this->assertArrayHasKey(Processes::NAME, $processInformation);
        $this->assertIsString($processInformation[Processes::NAME]);
        $this->assertGreaterThan(0, strlen($processInformation[Processes::NAME]), 'Name length');

        $this->assertArrayHasKey(Processes::UID, $processInformation);
        $this->assertIsInt($processInformation[Processes::UID]);
        $this->assertGreaterThan(0, $processInformation[Processes::PID], 'UID');
        $this->assertArrayHasKey(Processes::CPU, $processInformation);
        $this->assertIsFloat($processInformation[Processes::CPU]);
        $this->assertArrayHasKey(Processes::MEMORY, $processInformation);
        $this->assertIsFloat($processInformation[Processes::MEMORY]);
        $this->assertArrayHasKey(Processes::CMD, $processInformation);
        $this->assertIsString($processInformation[Processes::CMD]);
        $this->assertGreaterThan(0, strlen($processInformation[Processes::CMD]), 'Command length');

        $process->stop();

        $this->assertNull($process->getPid());
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isTerminated());
    }

    public function processesArgumentsProvider(): array
    {
        return [
            [true, false],
            [true, true],
            [false, false],
            [false, true],
        ];
    }

    public function testProcessesOnWindows(): void
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test run only on Windows');
        }

        $process = new Process(['tests/bin/while.exe']);

        $process->start();
        $this->pid = $process->getPid();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        $this->assertGreaterThan(0, $this->pid);

        $processes = new Processes(true);

        $this->assertFalse($processes->exists(null));
        $this->assertTrue($processes->exists($this->pid));

        $processInformation = $processes->get()[$this->pid];

        $this->assertArrayHasKey(Processes::PID, $processInformation);
        $this->assertIsInt($processInformation[Processes::PID]);
        $this->assertArrayHasKey(Processes::PPID, $processInformation);
        $this->assertIsInt($processInformation[Processes::PPID]);
        $this->assertArrayHasKey(Processes::NAME, $processInformation);
        $this->assertIsString($processInformation[Processes::NAME]);

        $process->stop();

        $this->assertNull($process->getPid());
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isTerminated());
    }
}
