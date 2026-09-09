<?php

namespace Symphograph\Bicycle\Debug;

class MethodStat
{
    public static array $all = [];

    private int $count = 0;
    private int $totalMicro = 0;
    private int $maxMicro = 0;

    public function add(int $microseconds): void
    {
        $this->count++;
        $this->totalMicro += $microseconds;
        if ($microseconds > $this->maxMicro) {
            $this->maxMicro = $microseconds;
        }
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getTotalMicro(): int
    {
        return $this->totalMicro;
    }

    public function getMaxMicro(): int
    {
        return $this->maxMicro;
    }

    public function getAvgMicro(): float
    {
        return $this->count > 0 ? $this->totalMicro / $this->count : 0.0;
    }

    // -------------------------------------------------------------------

    /**
     * Выводит сводную таблицу по накопленной статистике.
     * Автоматически подстраивается под CLI или веб-среду.
     */
    public static function printAll(): void
    {
        $stats = self::$all;
        if (empty($stats)) {
            self::log("No stats collected.");
            return;
        }

        // Сортировка по общему времени (по убыванию)
        uasort($stats, fn(MethodStat $a, MethodStat $b) =>
            $b->getTotalMicro() <=> $a->getTotalMicro()
        );

        // Преобразуем в массив строк для вывода
        $rows = [];
        foreach ($stats as $method => $stat) {
            $displayMethod = self::shortName($method);
            $rows[] = [
                'method' => $displayMethod,
                'count'  => $stat->getCount(),
                'total'  => $stat->getTotalMicro() / 1_000_000,
                'avg'    => $stat->getAvgMicro()   / 1_000_000,
                'max'    => $stat->getMaxMicro()   / 1_000_000,
            ];
        }

        // Определяем ширину колонок
        $colWidths = [
            'method' => max(
                max(array_map('strlen', array_column($rows, 'method'))),
                strlen('Method')
            ),
            'count'  => max(
                max(array_map(fn($r) => strlen((string)$r['count']), $rows)),
                strlen('Count')
            ),
            'total'  => strlen('Total'),
            'avg'    => strlen('Avg'),
            'max'    => strlen('Max'),
        ];

        // Для красоты добавим по 2 пробела запаса в каждую ячейку
        foreach ($colWidths as &$w) { $w += 2; }

        if (PHP_SAPI === 'cli') {
            self::printCliTable($rows, $colWidths);
        } else {
            self::printHtmlTable($rows);
        }
    }

    /**
     * ASCII-таблица для консоли.
     */
    private static function printCliTable(array $rows, array $w): void
    {
        $line = '+'
            . str_repeat('-', $w['method']) . '+'
            . str_repeat('-', $w['count'])  . '+'
            . str_repeat('-', $w['total'])  . '+'
            . str_repeat('-', $w['avg'])    . '+'
            . str_repeat('-', $w['max'])    . '+';

        echo $line . "\n";
        printf(
            "| %-{$w['method']}s | %{$w['count']}s | %{$w['total']}s | %{$w['avg']}s | %{$w['max']}s |\n",
            'Method', 'Count', 'Total', 'Avg', 'Max'
        );
        echo $line . "\n";

        foreach ($rows as $r) {
            printf(
                "| %-{$w['method']}s | %{$w['count']}d | %{$w['total']}s | %{$w['avg']}s | %{$w['max']}s |\n",
                $r['method'],
                $r['count'],
                Debug::formatTime($r['total']),
                Debug::formatTime($r['avg']),
                Debug::formatTime($r['max'])
            );
        }

        echo $line . "\n";
    }

    /**
     * HTML-таблица для браузера.
     */
    private static function printHtmlTable(array $rows): void
    {
        echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse;">';
        echo '<thead><tr><th>Method</th><th>Count</th><th>Total (sec)</th><th>Avg (sec)</th><th>Max (sec)</th></tr></thead>';
        echo '<tbody>';
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($r['method']) . '</td>';
            echo '<td>' . $r['count'] . '</td>';
            echo '<td>' . Debug::formatTime($r['total']) . '</td>';
            echo '<td>' . Debug::formatTime($r['avg'])   . '</td>';
            echo '<td>' . Debug::formatTime($r['max'])   . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    private static function shortName(string $fullMethod): string
    {
        // Ищем последний "\" или "::" / "->" — где заканчивается класс и начинается метод
        // Проще: разделим по последнему вхождению "\"
        $lastBackslash = strrpos($fullMethod, '\\');
        if ($lastBackslash !== false) {
            $str = substr($fullMethod, $lastBackslash + 1);
            return str_replace('->', '→', $str);
        }
        return str_replace('->', '→', $fullMethod);
    }


}