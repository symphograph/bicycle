<?php

namespace Symphograph\Bicycle\Debug;

class Debug
{
    public float|int $startTime;
    public int $memoryStart;



    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage();
    }

    public function memoryUsed(): int
    {
        return memory_get_usage() - $this->memoryStart;
    }

    public function  timeUsed(int $precision = 4): float
    {
        return round(microtime(true) - $this->startTime, $precision);
    }

    public function printHeader(string $title = 'test'): void
    {
        echo <<<HTML
            <!doctype html>
            <html lang="ru">
            <head>
                <meta charset="utf-8">
                <title>$title</title>
            </head>
            <body style="color: white; background-color: #18171B">
        HTML;

    }

    public function printFooter(): void
    {
        echo
            '<hr>Время выполнения скрипта: '
            . $this->timeUsed()
            . ' сек.';

        $this->printMemoryUsed();
        $formattedNumber = $this->formatMemoryUsage(memory_get_peak_usage());
        echo "<br>Пиковое использование памяти: " . $formattedNumber . " байт";
        echo '</body>';
    }

    public function printMemoryUsed(string $msg = 'Памяти использовано: '): void
    {
        echo "<br>$msg {$this->memoryUsed()} байт";
    }

    private function formatMemoryUsage(int $num): string
    {
        return number_format($num, 0, '', ' ');
    }
}