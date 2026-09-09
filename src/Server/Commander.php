<?php

namespace Symphograph\Bicycle\Server;

class Commander
{
    /**
     * Выполняет: supervisorctl restart all
     *
     * @return bool всегда true при успешном выполнении
     * @throws \RuntimeException при любой ошибке (недоступен proc_open,
     *                           не удалось запустить процесс, ненулевой код выхода)
     */
    public function restartAllWorkers(): bool
    {
        if (!function_exists('proc_open')) {
            throw new \RuntimeException('proc_open is not available on this system');
        }

        // Попробуем стандартный путь, иначе положимся на PATH
        $supervisorctl = '/usr/bin/supervisorctl';
        if (!is_executable($supervisorctl)) {
            // Если стандартного пути нет, используем просто имя команды
            $supervisorctl = 'supervisorctl';
        }

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open([$supervisorctl, 'restart', 'all'], $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException(
                sprintf('Failed to start process: %s restart all', $supervisorctl)
            );
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                sprintf(
                    'supervisorctl restart all failed. Exit code: %d. STDOUT: %s STDERR: %s',
                    $exitCode,
                    trim($stdout) ?: '(empty)',
                    trim($stderr) ?: '(empty)'
                )
            );
        }

        return true;
    }
}