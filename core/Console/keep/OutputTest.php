<?php

declare(strict_types=1);

namespace Plugs\Console\Support;

class OutputTest
{
    private const RESET = "\033[0m";
    private const RED   = "\033[31m";
    private const GREEN = "\033[32m";
    private const YELLOW = "\033[33m";
    private const BLUE  = "\033[34m";
    private const CYAN  = "\033[36m";

    public function header(string $text): void
    {
        $line = str_repeat('=', strlen($text) + 10);
        echo "\n" . self::CYAN . $line . self::RESET . "\n";
        echo self::CYAN . "     🚀 $text 🚀" . self::RESET . "\n";
        echo self::CYAN . $line . self::RESET . "\n\n";
    }

    public function line(string $text = ''): void
    {
        echo $text . PHP_EOL;
    }

    public function info(string $text): void
    {
        $this->line(self::BLUE . $text . self::RESET);
    }
    public function success(string $text): void
    {
        $this->line(self::GREEN . $text . self::RESET);
    }
    public function warning(string $text): void
    {
        $this->line(self::YELLOW . $text . self::RESET);
    }
    public function error(string $text): void
    {
        $this->line(self::RED . $text . self::RESET);
    }

    public function spinner(string $message, int $seconds = 2): void
    {
        $frames = ['-', '\\', '|', '/'];
        $end = time() + $seconds;
        $i = 0;
        while (time() < $end) {
            echo "\r" . $frames[$i++ % 4] . " $message";
            usleep(200000);
        }
        echo "\r✔ $message\n";
    }

    public function progressBar(int $max, callable $step): void
    {
        for ($i = 1; $i <= $max; $i++) {
            $step($i);
            $percent = (int)(($i / $max) * 100);
            echo "\r[" . str_repeat('#', (int)($percent / 5)) . str_repeat(' ', 20 - (int)($percent / 5)) . "] {$percent}%";
            usleep(100000);
        }
        echo "\n";
    }

    public function table(array $headers, array $rows): void
    {
        $cols = count($headers);
        $widths = array_map(static fn($h) => mb_strlen((string)$h), $headers);
        foreach ($rows as $row) {
            for ($i = 0; $i < $cols; $i++) {
                $widths[$i] = max($widths[$i], mb_strlen((string)($row[$i] ?? '')));
            }
        }
        $pad = fn($s, $w) => str_pad((string)$s, $w);
        $border = '+' . implode('+', array_map(fn($w) => str_repeat('-', $w + 2), $widths)) . "+\n";
        echo $border;
        echo '| ' . implode(' | ', array_map(fn($i, $h) => $pad($h, $widths[$i]), array_keys($headers), $headers)) . " |\n";
        echo $border;
        foreach ($rows as $row) {
            $cells = [];
            for ($i = 0; $i < $cols; $i++) {
                $cells[] = $pad($row[$i] ?? '', $widths[$i]);
            }
            echo '| ' . implode(' | ', $cells) . " |\n";
        }
        echo $border;
    }
}
