<?php

namespace Symphograph\Bicycle\Debug;

use Symphograph\Bicycle\Errors\AppErr;

class Debug
{
    public static array $log = [];
    public float|int $startTime;
    public int       $memoryStart;

    /** @var array<string, float> стек начал методов [key => startTime] */
    private static array $timers = [];


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
            <style>
                body {
                    margin: 20px;
                    color: #e6e6e6;
                    background-color: #18171B;
                    font-family: "IBM Plex Sans", sans-serif;
                    font-size: 14px;
                    line-height: 1.5;
                }

                code, pre {
                    font-family: "JetBrains Mono", monospace;
                }
            </style>
        </head>
        <body>
    HTML;
    }

    public function vdSep(): void
    {
        vd('--------------------');
    }

    public function printFooter(int $prec = 6): void
    {
        echo '<hr>';
        $this->printTimeUsed($prec);
        $this->printMemoryUsed();
        $formattedNumber = self::formatNum(memory_get_peak_usage());
        echo "<br>Пиковое использование памяти: " . $formattedNumber . " байт";
        echo '</body>';
    }

    public function printTimeUsed(int $prec = 6): void
    {
        $time = $this->timeUsed($prec);
        $label = 'Время выполнения: ';

        if ($time < 60) {
            // меньше минуты — сохраняем дробную часть
            echo $label
                . number_format($time, $prec, '.', '')
                . ' сек.';
        } elseif ($time < 3600) {
            // минуты и секунды, без дробной части
            $minutes = floor($time / 60);
            $seconds = floor($time - $minutes * 60);
            echo "$label{$minutes}м {$seconds}с";
        } else {
            // часы, минуты и секунды
            $hours = floor($time / 3600);
            $remainder = $time - $hours * 3600;
            $minutes = floor($remainder / 60);
            $seconds = floor($remainder - $minutes * 60);
            echo "$label{$hours}ч {$minutes}м {$seconds}с";
        }
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

    public static function log(string $msg): void
    {
        self::$log[] = $msg;
    }

    // ----------------------------------------------------------

    /**
     * Начать замер для текущего метода (вызывается в начале метода).
     * @param string|null $label если null, имя метода определится автоматически
     */
    public static function startMethod(?string $label = null): void
    {
        $key = $label ?? self::getCallerMethod();
        self::$timers[$key] = microtime(true);
    }

    public static function endMethod(?string $label = null): void
    {
        $key = $label ?? self::getCallerMethod();
        $start = self::$timers[$key] ?? null;

        if ($start === null) {
            self::log("$key: timer not started");
            return;
        }

        $seconds = microtime(true) - $start;
        $micro   = (int) round($seconds * 1_000_000);

        // --- обновляем агрегированную статистику ---
        if (!isset(MethodStat::$all[$key])) {
            MethodStat::$all[$key] = new MethodStat();
        }
        MethodStat::$all[$key]->add($micro);

        // обычное логирование
        $dur = self::formatTime($seconds);
        self::log("$key: $dur sec");

        unset(self::$timers[$key]);
    }

    /**
     * Форматирует секунды без экспоненты.
     */
    public static function formatTime(float $seconds, int $prec = 6): string
    {
        // number_format всегда даёт фиксированную запятую
        $formatted = number_format($seconds, $prec, '.', '');
        // убираем незначащие нули (но оставляем хотя бы один после точки, если есть дробная часть)
        if (str_contains($formatted, '.')) {
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, '.'); // убрать точку, если целое
        }
        return $formatted;
    }

    /**
     * Определяет имя метода, который вызвал Debug (т.е. вызывающий код).
     */
    private static function getCallerMethod(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        // $trace[0] — вызов getCallerMethod
        // $trace[1] — вызов startMethod/endMethod
        // $trace[2] — целевой метод
        $caller = $trace[2] ?? null;
        if (!$caller) return 'unknown';
        $class = $caller['class'] ?? '';
        $type  = $caller['type'] ?? '';
        $func  = $caller['function'] ?? 'unknown';
        return $class !== '' ? $class . $type . $func : $func;
    }

    public static function vdTimers(): void
    {
        vd(self::$log);
    }
}