<?php

use Devium\Processes\Exceptions\PIDCanOnlyBeAnIntegerException;
use PHPUnit\Framework\TestCase;
use Devium\Processes\Processes;
use Symfony\Component\Process\Process;

class ProcessesTest extends TestCase
{
    /** @var int */
    private $pid = 0;

    /**
     * @dataProvider processesArgumentsProvider
     * @param null|bool $all
     * @param null|bool $multi
     * @throws PIDCanOnlyBeAnIntegerException
     */
    public function testProcessesOnUnix(?bool $all, ?bool $multi): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            $this->markTestSkipped('This test runs only on Unix');
        }

        $process = new Process(['tests/bin/while']);

        $process->start();
        $this->pid = $process->getPid();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        $this->assertGreaterThan(0, $this->pid);

        $processes = new Processes($all, $multi);

        $this->assertFalse($processes->exists());
        $this->assertTrue($processes->exists($this->pid));

        $processInformation = $processes[$this->pid];

        $this->assertArrayHasKey(Processes::PID, $processInformation);
        $this->assertIsInt($processInformation[Processes::PID]);
        $this->assertGreaterThan(0, $processInformation[Processes::PID], 'PID');
        $this->assertArrayHasKey(Processes::PPID, $processInformation);
        $this->assertIsInt($processInformation[Processes::PPID]);
        $this->assertGreaterThanOrEqual(0, $processInformation[Processes::PPID], 'PPID');
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
        // in busybox environment COMMAND is always empty
        if ($processes->getResultType() !== Processes::BUSY_BOX_RESULT) {
            $this->assertGreaterThan(0, strlen($processInformation[Processes::CMD]), 'Command length');
        }

        $process->stop();

        $this->assertNull($process->getPid());
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isTerminated());

        $this->assertGreaterThanOrEqual(count($processes->rescan()), count($processes));

        $this->expectException(PIDCanOnlyBeAnIntegerException::class);
        $processes->exists('string');
    }

    public function processesArgumentsProvider(): array
    {
        return [
            [null, null],
            [true, false],
            [true, true],
            [false, false],
            [false, true],
        ];
    }

    /**
     * @throws PIDCanOnlyBeAnIntegerException
     */
    public function testProcessesOnDarwin(): void
    {
        if (PHP_OS_FAMILY !== 'Darwin') {
            $this->markTestSkipped('This test runs only on Darwin');
        }

        $process = new Process(['tests/bin/while_darwin']);

        $process->start();
        $this->pid = $process->getPid();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        $this->assertGreaterThan(0, $this->pid);

        $processes = new Processes(true);

        $this->assertFalse($processes->exists());
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

    /**
     * @throws PIDCanOnlyBeAnIntegerException
     */
    public function testProcessesOnWindows(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('This test runs only on Windows');
        }

        $process = new Process(['tests/bin/while.exe']);

        $process->start();
        $this->pid = $process->getPid();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        $this->assertGreaterThan(0, $this->pid);

        $processes = new Processes(true);

        $this->assertFalse($processes->exists());
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
