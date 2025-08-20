<?php
declare(strict_types=1);

namespace Plugs\Console\Support;

class Output
{
    // Reset
    private const RESET = "\033[0m";
    
    // Standard colors
    private const BLACK = "\033[30m";
    private const RED = "\033[31m";
    private const GREEN = "\033[32m";
    private const YELLOW = "\033[33m";
    private const BLUE = "\033[34m";
    private const MAGENTA = "\033[35m";
    private const CYAN = "\033[36m";
    private const WHITE = "\033[37m";
    
    // Bright colors
    private const BRIGHT_BLACK = "\033[90m";
    private const BRIGHT_RED = "\033[91m";
    private const BRIGHT_GREEN = "\033[92m";
    private const BRIGHT_YELLOW = "\033[93m";
    private const BRIGHT_BLUE = "\033[94m";
    private const BRIGHT_MAGENTA = "\033[95m";
    private const BRIGHT_CYAN = "\033[96m";
    private const BRIGHT_WHITE = "\033[97m";
    
    // Background colors
    private const BG_RED = "\033[41m";
    private const BG_GREEN = "\033[42m";
    private const BG_YELLOW = "\033[43m";
    private const BG_BLUE = "\033[44m";
    private const BG_MAGENTA = "\033[45m";
    private const BG_CYAN = "\033[46m";
    
    // Text styles
    private const BOLD = "\033[1m";
    private const DIM = "\033[2m";
    private const ITALIC = "\033[3m";
    private const UNDERLINE = "\033[4m";
    private const BLINK = "\033[5m";
    private const REVERSE = "\033[7m";
    private const STRIKETHROUGH = "\033[9m";
    
    // Modern gradient colors (256-color mode)
    private const GRADIENT_PURPLE = "\033[38;5;135m";
    private const GRADIENT_PINK = "\033[38;5;211m";
    private const GRADIENT_ORANGE = "\033[38;5;208m";
    private const GRADIENT_TEAL = "\033[38;5;80m";

    public function header(string $text): void
    {
        // Account for emoji width (🚀 = 2 display chars but 4 bytes)
        $textWithEmojis = "🚀 $text 🚀";
        $displayWidth = mb_strwidth($textWithEmojis);
        $width = max(60, $displayWidth + 10);
        $padding = ($width - $displayWidth) / 2;
        
        echo "\n";
        echo self::GRADIENT_PURPLE . self::BOLD . "╔" . str_repeat("═", $width - 2) . "╗" . self::RESET . "\n";
        echo self::GRADIENT_PURPLE . self::BOLD . "║" . str_repeat(" ", (int)floor($padding)) . "🚀 " . self::BRIGHT_WHITE . self::BOLD . $text . self::GRADIENT_PURPLE . " 🚀" . str_repeat(" ", (int)ceil($padding)) . "║" . self::RESET . "\n";
        echo self::GRADIENT_PURPLE . self::BOLD . "╚" . str_repeat("═", $width - 2) . "╝" . self::RESET . "\n\n";
    }

    public function subHeader(string $text): void
    {
        echo "\n" . self::GRADIENT_TEAL . self::BOLD . "▶ " . $text . self::RESET . "\n";
        echo self::DIM . str_repeat("─", mb_strwidth($text) + 2) . self::RESET . "\n";
    }

    public function line(string $text = ''): void
    {
        echo $text . PHP_EOL;
    }

    public function info(string $text): void
    {
        $this->line(self::BRIGHT_BLUE . "ℹ " . self::RESET . $text);
    }

    public function success(string $text): void
    {
        $this->line(self::BRIGHT_GREEN . "✓ " . self::RESET . $text);
    }

    public function warning(string $text): void
    {
        $this->line(self::BRIGHT_YELLOW . "⚠ " . self::RESET . $text);
    }

    public function error(string $text): void
    {
        $this->line(self::BRIGHT_RED . "✗ " . self::RESET . $text);
    }

    public function critical(string $text): void
    {
        $this->line(self::BG_RED . self::BRIGHT_WHITE . self::BOLD . " CRITICAL " . self::RESET . " " . self::BRIGHT_RED . $text . self::RESET);
    }

    public function note(string $text): void
    {
        $this->line(self::GRADIENT_PINK . "📝 Note: " . self::RESET . self::DIM . $text . self::RESET);
    }

    public function debug(string $text): void
    {
        $this->line(self::DIM . "🐛 Debug: " . $text . self::RESET);
    }

    public function spinner(string $message, int|callable $secondsOrCallback = 2): void
    {
        $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $i = 0;
        
        if (is_callable($secondsOrCallback)) {
            // Use closure to determine when to stop
            while (!$secondsOrCallback()) {
                echo "\r" . self::BRIGHT_CYAN . $frames[$i++ % count($frames)] . self::RESET . " $message";
                usleep(120000);
                
                // Prevent infinite loops - max 60 seconds
                if ($i > 500) {
                    $this->warning("Spinner timeout reached");
                    break;
                }
            }
        } else {
            // Use time-based approach
            $end = time() + $secondsOrCallback;
            while (time() < $end) {
                echo "\r" . self::BRIGHT_CYAN . $frames[$i++ % count($frames)] . self::RESET . " $message";
                usleep(120000);
            }
        }
        
        echo "\r" . self::BRIGHT_GREEN . "✔" . self::RESET . " $message" . str_repeat(" ", 10) . "\n";
    }

    public function progressBar(int $max, callable $step, string $label = 'Progress'): void
    {
        echo self::BOLD . $label . ":" . self::RESET . "\n";
        
        for ($i = 1; $i <= $max; $i++) {
            $step($i);
            $percent = (int)(($i / $max) * 100);
            $filled = (int)($percent / 2.5); // 40 chars wide
            $empty = 40 - $filled;
            
            $bar = str_repeat("█", $filled) . str_repeat("░", $empty);
            $color = $percent < 33 ? self::BRIGHT_RED : ($percent < 66 ? self::BRIGHT_YELLOW : self::BRIGHT_GREEN);
            
            echo "\r" . $color . "▐" . $bar . "▌" . self::RESET . " " . self::BOLD . $percent . "%" . self::RESET . " ($i/$max)";
            usleep(50000);
        }
        echo "\n" . self::BRIGHT_GREEN . "✅ Completed!" . self::RESET . "\n\n";
    }

    public function table(array $headers, array $rows): void
    {
        if (empty($headers) || empty($rows)) {
            $this->warning("No data to display in table");
            return;
        }

        $cols = count($headers);
        $widths = array_map(static fn($h) => mb_strwidth((string)$h), $headers);
        
        foreach ($rows as $row) {
            for ($i = 0; $i < $cols; $i++) {
                $widths[$i] = max($widths[$i], mb_strwidth((string)($row[$i] ?? '')));
            }
        }

        $pad = fn($s, $w) => str_pad((string)$s, $w + (strlen($s) - mb_strwidth($s)));
        
        // Top border
        echo self::BRIGHT_CYAN . "╭" . implode("┬", array_map(fn($w) => str_repeat("─", $w + 2), $widths)) . "╮" . self::RESET . "\n";
        
        // Headers
        echo self::BRIGHT_CYAN . "│" . self::RESET;
        foreach ($headers as $i => $header) {
            echo " " . self::BOLD . self::BRIGHT_WHITE . $pad($header, $widths[$i]) . self::RESET . " " . self::BRIGHT_CYAN . "│" . self::RESET;
        }
        echo "\n";
        
        // Header separator
        echo self::BRIGHT_CYAN . "├" . implode("┼", array_map(fn($w) => str_repeat("─", $w + 2), $widths)) . "┤" . self::RESET . "\n";
        
        // Rows
        foreach ($rows as $rowIndex => $row) {
            $rowColor = $rowIndex % 2 === 0 ? self::RESET : self::DIM;
            echo self::BRIGHT_CYAN . "│" . self::RESET;
            
            for ($i = 0; $i < $cols; $i++) {
                $cellValue = $row[$i] ?? '';
                echo " " . $rowColor . $pad($cellValue, $widths[$i]) . self::RESET . " " . self::BRIGHT_CYAN . "│" . self::RESET;
            }
            echo "\n";
        }
        
        // Bottom border
        echo self::BRIGHT_CYAN . "╰" . implode("┴", array_map(fn($w) => str_repeat("─", $w + 2), $widths)) . "╯" . self::RESET . "\n\n";
    }

    public function box(string $content, string $title = '', string $type = 'info'): void
    {
        $lines = explode("\n", $content);
        
        // Calculate actual display width accounting for multibyte characters and ANSI codes
        $maxContentWidth = 0;
        foreach ($lines as $line) {
            $maxContentWidth = max($maxContentWidth, mb_strwidth($this->stripAnsiCodes($line)));
        }
        
        $titleWidth = mb_strwidth($title);
        $contentWidth = max($maxContentWidth, $titleWidth);
        $boxWidth = $contentWidth + 4; // 2 spaces on each side

        $colors = [
            'info' => self::BRIGHT_BLUE,
            'success' => self::BRIGHT_GREEN,
            'warning' => self::BRIGHT_YELLOW,
            'error' => self::BRIGHT_RED,
            'note' => self::GRADIENT_PINK
        ];

        $color = $colors[$type] ?? self::BRIGHT_BLUE;

        // Top border
        echo $color . "╭" . str_repeat("─", $boxWidth - 2) . "╮" . self::RESET . "\n";
        
        // Title
        if ($title) {
            $titlePadding = ($boxWidth - $titleWidth - 4) / 2;
            echo $color . "│" . self::RESET . str_repeat(" ", (int)floor($titlePadding)) . self::BOLD . $title . self::RESET . str_repeat(" ", (int)ceil($titlePadding)) . $color . "│" . self::RESET . "\n";
            echo $color . "├" . str_repeat("─", $boxWidth - 2) . "┤" . self::RESET . "\n";
        }
        
        // Content
        foreach ($lines as $line) {
            $cleanLine = $this->stripAnsiCodes($line);
            $lineWidth = mb_strwidth($cleanLine);
            $padding = $contentWidth - $lineWidth;
            echo $color . "│" . self::RESET . " " . $line . str_repeat(" ", $padding + 1) . $color . "│" . self::RESET . "\n";
        }
        
        // Bottom border
        echo $color . "╰" . str_repeat("─", $boxWidth - 2) . "╯" . self::RESET . "\n\n";
    }

    /**
     * Strip ANSI escape codes from string for accurate width calculation
     */
    private function stripAnsiCodes(string $text): string
    {
        return preg_replace('/\033\[[0-9;]*m/', '', $text);
    }

    public function countdown(int $seconds, string $message = 'Starting in'): void
    {
        for ($i = $seconds; $i > 0; $i--) {
            echo "\r" . self::BRIGHT_YELLOW . $message . " " . self::BOLD . $i . self::RESET . "s...";
            sleep(1);
        }
        echo "\r" . self::BRIGHT_GREEN . "🚀 Let's go!" . self::RESET . str_repeat(" ", 20) . "\n";
    }

    public function gradient(string $text): void
    {
        $colors = [
            self::GRADIENT_PURPLE,
            self::GRADIENT_PINK,
            self::GRADIENT_ORANGE,
            self::BRIGHT_YELLOW,
            self::BRIGHT_GREEN,
            self::GRADIENT_TEAL,
            self::BRIGHT_CYAN,
            self::BRIGHT_BLUE
        ];
        
        $chars = mb_str_split($text);
        $len = count($chars);
        $colorCount = count($colors);
        
        for ($i = 0; $i < $len; $i++) {
            $colorIndex = (int)(($i / max(1, $len - 1)) * ($colorCount - 1));
            echo $colors[$colorIndex] . $chars[$i];
        }
        echo self::RESET . "\n";
    }

    public function banner(string $text): void
    {
        echo "\n";
        $bannerChars = str_repeat("█", mb_strwidth($text) + 10);
        $this->gradient($bannerChars);
        echo self::BOLD . self::BRIGHT_WHITE . str_repeat(" ", 5) . $text . str_repeat(" ", 5) . self::RESET . "\n";
        $this->gradient($bannerChars);
        echo "\n";
    }

    public function askConfirmation(string $question): bool
    {
        echo self::BRIGHT_YELLOW . "❓ " . $question . " " . self::DIM . "(y/N)" . self::RESET . " ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        return strtolower($input) === 'y' || strtolower($input) === 'yes';
    }
}