<?php

use PHPUnit\Framework\TestCase;
use Devium\Processes\Processes;
use Symfony\Component\Process\Process;

class ProcessesTest extends TestCase
{
    public function testProcessesOnUnix(): void
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test run only on Unix');
        }

        $process = Process::fromShellCommandline('tests/bin/while');

        $process->start();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        while ($process->isRunning()) {
            $this->assertGreaterThan(0, $process->getPid());

            $processes = new Processes(true);
            $this->assertTrue($processes->exists($process->getPid()));

            $p = $processes->get()[$process->getPid()];
            $this->assertArrayHasKey(Processes::PID, $p);
            $this->assertIsInt($p[Processes::PID]);
            $this->assertArrayHasKey(Processes::PPID, $p);
            $this->assertIsInt($p[Processes::PPID]);
            $this->assertArrayHasKey(Processes::NAME, $p);
            $this->assertIsString($p[Processes::NAME]);

            $this->assertArrayHasKey(Processes::UID, $p);
            $this->assertIsInt($p[Processes::UID]);
            $this->assertArrayHasKey(Processes::CPU, $p);
            $this->assertIsFloat($p[Processes::CPU]);
            $this->assertArrayHasKey(Processes::MEMORY, $p);
            $this->assertIsFloat($p[Processes::MEMORY]);
            $this->assertArrayHasKey(Processes::CMD, $p);
            $this->assertIsString($p[Processes::CMD]);

            $process->stop(0, 9);
        }

        $this->assertNull($process->getPid());
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isTerminated());
    }

    public function testProcessesOnWindows(): void
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test run only on Windows');
        }

        $process = Process::fromShellCommandline('tests/bin/while.exe');

        $process->start();

        $this->assertTrue($process->isStarted());
        $this->assertFalse($process->isTerminated());

        while ($process->isRunning()) {
            $this->assertGreaterThan(0, $process->getPid());

            $processes = new Processes(true);
            $this->assertTrue($processes->exists($process->getPid()));

            $p = $processes->get()[$process->getPid()];

            $this->assertArrayHasKey(Processes::PID, $p);
            $this->assertIsInt($p[Processes::PID]);
            $this->assertArrayHasKey(Processes::PPID, $p);
            $this->assertIsInt($p[Processes::PPID]);
            $this->assertArrayHasKey(Processes::NAME, $p);
            $this->assertIsString($p[Processes::NAME]);

            $process->stop(0, 9);
        }

        $this->assertNull($process->getPid());
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isTerminated());
    }
}
