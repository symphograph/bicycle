<?php

namespace Symphograph\Bicycle\Debug;

use Symphograph\Bicycle\Errors\AppErr;

class Debug
{
    public float|int $startTime;
    public int       $memoryStart;


    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage();
    }

    public function memoryUsed(): int
    {
        return memory_get_usage() - $this->memoryStart;
    }

    public function timeUsed(int $prec = 6): float
    {
        return round(microtime(true) - $this->startTime, $prec);
    }

    public function dur(float|int $time): float
    {
        return round($this->timeUsed() - $time);
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

    public function printFooter(int $prec = 6): void
    {
        echo
            '<hr>Время выполнения скрипта: '
            . number_format($this->timeUsed($prec), $prec, '.', '')
            . ' сек.';

        $this->printMemoryUsed();
        $formattedNumber = self::formatNum(memory_get_peak_usage());
        echo "<br>Пиковое использование памяти: " . $formattedNumber . " байт";
        echo '</body>';
    }

    public function printMemoryUsed(string $msg = 'Памяти использовано: '): void
    {
        $fNumber = self::formatNum($this->memoryUsed());
        echo "<br>$msg $fNumber байт";
    }

    private static function formatNum(int $num): string
    {
        return number_format($num, 0, '', ' ');
    }

    public static function testValues(array $values, callable $testFunction): void
    {
        if (count($values) !== 2) throw new AppErr('invalid array for test');

        foreach ($values as $test) {
            $result = $testFunction($test['input']);

            echo "Input: " . htmlspecialchars($test['input'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . "<br>";
            echo "Expected: " . htmlspecialchars($test['expected'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . "<br>";
            echo "Result: " . htmlspecialchars($result, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "<br>";
            echo ($result === $test['expected'] ? "✔ Passed" : "✘ Failed") . "<br>";
            echo str_repeat("-", 40) . "<br>";
        }
    }
}