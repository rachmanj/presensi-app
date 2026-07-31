<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as FrameworkServeCommand;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;

class ServeCommand extends FrameworkServeCommand
{
    protected function startProcess($hasEnvironment)
    {
        if (windows_os()) {
            $process = new Process($this->serverCommand(), public_path());

            $this->trap(fn () => [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGUSR2, SIGQUIT], function ($signal) use ($process) {
                if ($process->isRunning()) {
                    $process->stop(10, $signal);
                }

                exit;
            });

            $process->start($this->handleProcessOutput());

            return $process;
        }

        return parent::startProcess($hasEnvironment);
    }
}
