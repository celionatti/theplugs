<?php

declare(strict_types=1);

namespace Plugs\Console\Support;

class Output
{
    private const RESET = "\033[0m";
    private const RED   = "\033[31m";
    private const GREEN = "\033[32m";
    private const YELLOW= "\033[33m";
    private const BLUE  = "\033[34m";

    public function line(string $text = ''): void { echo $text . PHP_EOL; }

    public function info(string $text): void    { $this->line(self::BLUE . $text . self::RESET); }
    public function success(string $text): void { $this->line(self::GREEN . $text . self::RESET); }
    public function warning(string $text): void { $this->line(self::YELLOW . $text . self::RESET); }
    public function error(string $text): void   { $this->line(self::RED . $text . self::RESET); }

    /**
     * Render a table with auto widths.
     * @param array<int,array<int,string>> $rows
     */
    public function table(array $headers, array $rows): void
    {
        $cols = count($headers);
        $widths = array_map(static fn($h) => mb_strlen((string)$h), $headers);
        foreach ($rows as $row) {
            for ($i=0; $i<$cols; $i++) {
                $widths[$i] = max($widths[$i], mb_strlen((string)($row[$i] ?? '')));
            }
        }
        $pad = fn($s,$w) => str_pad((string)$s, $w);
        $border = '+' . implode('+', array_map(fn($w)=> str_repeat('-', $w+2), $widths)) . "+\n";
        echo $border;
        echo '| ' . implode(' | ', array_map(fn($i,$h)=> $pad($h,$widths[$i]), array_keys($headers), $headers)) . " |\n";
        echo $border;
        foreach ($rows as $row) {
            $cells = [];
            for ($i=0;$i<$cols;$i++) { $cells[] = $pad($row[$i] ?? '', $widths[$i]); }
            echo '| ' . implode(' | ', $cells) . " |\n";
        }
        echo $border;
    }
}