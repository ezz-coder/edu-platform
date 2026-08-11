<?php

namespace App\Utilities;

/**
 * Minimal, dependency-free QR Code generator.
 *
 * Supports byte mode, error-correction level L, and QR versions 1-4
 * (single-block structure). This is enough to encode the permanent
 * student QR token (40 characters) and render a scannable SVG.
 *
 * Implements the ISO/IEC 18004 Model 2 algorithm:
 *  - Galois Field (256) arithmetic
 *  - Reed-Solomon error correction
 *  - Function pattern placement (finders, timing, alignment, dark module)
 *  - Data placement with the 8 standard mask patterns
 *  - Format information bits
 *
 * No external packages are used, per project constraints.
 */
class QrCodeGenerator
{
    /** @var array<int,int> */
    private array $gfExp = [];

    /** @var array<int,int> */
    private array $gfLog = [];

    /**
     * Data-capacity (data codewords) per version for EC level L.
     * Versions 1-4 only (single block each).
     *
     * @var array<int,int>
     */
    private array $capacity = [
        1 => 19,
        2 => 34,
        3 => 55,
        4 => 80,
    ];

    /**
     * EC codewords per block for EC level L (versions 1-4).
     *
     * @var array<int,int>
     */
    private array $ecLength = [
        1 => 7,
        2 => 10,
        3 => 15,
        4 => 20,
    ];

    /**
     * Alignment-pattern center coordinates per version (1-4).
     *
     * @var array<int,array<int,int>>
     */
    private array $alignmentPositions = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
    ];

    public function __construct()
    {
        $this->initGalois();
    }

    /**
     * Render a QR code for the given text as an SVG string.
     */
    public function renderSvg(string $text, int $size = 320, int $quietZone = 4): string
    {
        $version = $this->chooseVersion($text);
        $modules = $this->buildMatrix($text, $version);
        $dim = count($modules);
        $total = $dim + $quietZone * 2;

        $rects = '';
        for ($y = 0; $y < $dim; $y++) {
            for ($x = 0; $x < $dim; $x++) {
                if ($modules[$y][$x] & 1) {
                    $px = $x + $quietZone;
                    $py = $y + $quietZone;
                    $rects .= sprintf('<rect x="%d" y="%d" width="1" height="1"/>', $px, $py);
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" shape-rendering="crispEdges"><rect width="%d" height="%d" fill="#ffffff"/><g fill="#000000">%s</g></svg>',
            $total,
            $total,
            $size,
            $size,
            $total,
            $total,
            $rects
        );
    }

    /**
     * Pick the smallest version (1-4) that fits the text.
     */
    private function chooseVersion(string $text): int
    {
        $len = strlen($text);
        foreach ($this->capacity as $version => $dataCodewords) {
            $availableBits = $dataCodewords * 8;
            // 4 (mode) + 8 (count, versions <= 9) + len*8 + up to 4 terminator.
            $neededBits = 4 + 8 + $len * 8;
            if ($neededBits <= $availableBits) {
                return $version;
            }
        }

        throw new \RuntimeException('QR data too long for supported versions (1-4, EC-L).');
    }

    /**
     * Build the full module matrix (with mask already applied & format bits set).
     *
     * @return array<int,array<int,int>>
     */
    private function buildMatrix(string $text, int $version): array
    {
        $size = 17 + 4 * $version;
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        $modules = array_fill(0, $size, array_fill(0, $size, 0));
        $this->drawFunctionPatterns($modules, $reserved, $size, $version);

        $dataCodewords = $this->encodeData($text, $version);
        $ecCodewords = $this->reedSolomon($dataCodewords, $this->ecLength[$version]);
        $allCodewords = array_merge($dataCodewords, $ecCodewords);

        $this->placeData($modules, $reserved, $allCodewords, $size);

        // Choose the best mask (lowest penalty) by cloning the base matrix.
        $baseModules = $modules;
        $baseReserved = $reserved;

        $bestMask = 0;
        $bestPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $work = $baseModules;
            $this->applyMask($work, $baseReserved, $size, $mask);
            $this->drawFormat($work, $size, $mask);
            $penalty = $this->penalty($work, $size);
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMask = $mask;
            }
        }

        // Apply the chosen mask permanently.
        $this->applyMask($modules, $reserved, $size, $bestMask);
        $this->drawFormat($modules, $size, $bestMask);

        return $modules;
    }

    /**
     * Encode text into data codewords (byte mode, EC level L).
     *
     * @return array<int,int>
     */
    private function encodeData(string $text, int $version): array
    {
        $dataBits = '';
        // Mode indicator: byte mode = 0100
        $dataBits .= '0100';
        // Character count indicator (8 bits for versions <= 9).
        $dataBits .= str_pad(decbin(strlen($text)), 8, '0', STR_PAD_LEFT);
        // Data bytes.
        for ($i = 0; $i < strlen($text); $i++) {
            $dataBits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
        }
        // Terminator (up to 4 zero bits).
        $capacityBits = $this->capacity[$version] * 8;
        $remaining = $capacityBits - strlen($dataBits);
        if ($remaining > 0) {
            $dataBits .= str_repeat('0', min(4, $remaining));
        }
        // Pad to byte boundary.
        while (strlen($dataBits) % 8 !== 0) {
            $dataBits .= '0';
        }
        // Pad bytes.
        $padBytes = [0xEC, 0x11];
        $p = 0;
        while (strlen($dataBits) < $capacityBits) {
            $dataBits .= str_pad(decbin($padBytes[$p % 2]), 8, '0', STR_PAD_LEFT);
            $p++;
        }

        $codewords = [];
        for ($i = 0; $i < strlen($dataBits); $i += 8) {
            $codewords[] = bindec(substr($dataBits, $i, 8));
        }

        return $codewords;
    }

    /**
     * Reed-Solomon error-correction codewords.
     *
     * @param array<int,int> $data
     * @return array<int,int>
     */
    private function reedSolomon(array $data, int $ecLength): array
    {
        $generator = $this->rsGeneratorPolynomial($ecLength);
        $res = array_pad([], count($data) + $ecLength, 0);
        for ($i = 0; $i < count($data); $i++) {
            $res[$i] = $data[$i];
        }

        for ($i = 0; $i < count($data); $i++) {
            $coef = $res[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($generator); $j++) {
                    $res[$i + $j] ^= $this->gfMul($generator[$j], $coef);
                }
            }
        }

        return array_slice($res, count($data));
    }

    /**
     * @return array<int,int>
     */
    private function rsGeneratorPolynomial(int $degree): array
    {
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $poly = $this->polyMul($poly, [1, $this->gfExp[$i]]);
        }

        return $poly;
    }

    /**
     * @param array<int,int> $a
     * @param array<int,int> $b
     * @return array<int,int>
     */
    private function polyMul(array $a, array $b): array
    {
        $result = array_fill(0, count($a) + count($b) - 1, 0);
        for ($i = 0; $i < count($a); $i++) {
            if ($a[$i] === 0) {
                continue;
            }
            for ($j = 0; $j < count($b); $j++) {
                $result[$i + $j] ^= $this->gfMul($a[$i], $b[$j]);
            }
        }

        return $result;
    }

    private function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return $this->gfExp[($this->gfLog[$a] + $this->gfLog[$b]) % 255];
    }

    private function initGalois(): void
    {
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $this->gfExp[$i] = $x;
            $this->gfLog[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11d;
            }
        }
        $this->gfExp[255] = $this->gfExp[0];
    }

    /**
     * @param array<int,array<int,int>> $modules
     * @param array<int,array<int,bool>> $reserved
     */
    private function drawFunctionPatterns(array &$modules, array &$reserved, int $size, int $version): void
    {
        // Finders + separators (top-left, top-right, bottom-left).
        $this->drawFinder($modules, $reserved, 0, 0);
        $this->drawFinder($modules, $reserved, $size - 7, 0);
        $this->drawFinder($modules, $reserved, 0, $size - 7);

        // Timing patterns.
        for ($i = 8; $i < $size - 8; $i++) {
            $dark = $i % 2 === 0;
            if (! $reserved[6][$i]) {
                $modules[6][$i] = $dark ? 1 : 0;
                $reserved[6][$i] = true;
            }
            if (! $reserved[$i][6]) {
                $modules[$i][6] = $dark ? 1 : 0;
                $reserved[$i][6] = true;
            }
        }

        // Alignment patterns.
        $coords = $this->alignmentPositions[$version];
        if (! empty($coords)) {
            $centers = [];
            foreach ($coords as $cx) {
                foreach ($coords as $cy) {
                    $centers[] = [$cx, $cy];
                }
            }
            foreach ($centers as [$cx, $cy]) {
                // Skip if it overlaps a finder pattern area.
                if (($cx <= 7 && $cy <= 7) || ($cx >= $size - 8 && $cy <= 7) || ($cx <= 7 && $cy >= $size - 8)) {
                    continue;
                }
                $this->drawAlignment($modules, $reserved, $cx, $cy);
            }
        }

        // Dark module.
        $modules[$size - 8][8] = 1;
        $reserved[$size - 8][8] = true;

        // Reserve the entire format-information rows/columns (row 8 and col 8)
        // so data placement and masking never touch them. This covers both
        // format copies near the top-left and bottom-right.
        for ($i = 0; $i < $size; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
    }

    /**
     * @param array<int,array<int,int>> $modules
     * @param array<int,array<int,bool>> $reserved
     */
    private function drawFinder(array &$modules, array &$reserved, int $x, int $y): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $nx = $x + $dx;
                $ny = $y + $dy;
                if ($nx < 0 || $ny < 0 || $nx >= count($modules) || $ny >= count($modules)) {
                    continue;
                }
                $dark = ($dx >= 0 && $dx <= 6 && ($dy === 0 || $dy === 6))
                    || ($dy >= 0 && $dy <= 6 && ($dx === 0 || $dx === 6))
                    || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4);
                $modules[$ny][$nx] = $dark ? 1 : 0;
                $reserved[$ny][$nx] = true;
            }
        }
    }

    /**
     * @param array<int,array<int,int>> $modules
     * @param array<int,array<int,bool>> $reserved
     */
    private function drawAlignment(array &$modules, array &$reserved, int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $nx = $cx + $dx;
                $ny = $cy + $dy;
                $dark = max(abs($dx), abs($dy)) !== 1;
                $modules[$ny][$nx] = $dark ? 1 : 0;
                $reserved[$ny][$nx] = true;
            }
        }
    }

    /**
     * @param array<int,array<int,int>> $modules
     * @param array<int,array<int,bool>> $reserved
     * @param array<int,int> $codewords
     */
    private function placeData(array &$modules, array $reserved, array $codewords, int $size): void
    {
        $bitIndex = 0;
        $totalBits = count($codewords) * 8;

        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $y = (($vert % 2) === 0) ? $size - 1 - $vert : $vert;
                    if (! $reserved[$y][$x]) {
                        $dark = false;
                        if ($bitIndex < $totalBits) {
                            $byte = $codewords[intdiv($bitIndex, 8)];
                            $bit = ($byte >> (7 - ($bitIndex % 8))) & 1;
                            $dark = $bit === 1;
                        }
                        $modules[$y][$x] = $dark ? 1 : 0;
                        $bitIndex++;
                    }
                }
            }
        }
    }

    /**
     * @param array<int,array<int,int>> $modules
     * @param array<int,array<int,bool>> $reserved
     */
    private function applyMask(array &$modules, array $reserved, int $size, int $mask): void
    {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($reserved[$y][$x]) {
                    continue;
                }
                if ($this->maskCondition($mask, $x, $y)) {
                    $modules[$y][$x] ^= 1;
                }
            }
        }
    }

    private function maskCondition(int $mask, int $x, int $y): bool
    {
        return match ($mask) {
            0 => ($y + $x) % 2 === 0,
            1 => $y % 2 === 0,
            2 => $x % 3 === 0,
            3 => ($y + $x) % 3 === 0,
            4 => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
            5 => (($y * $x) % 2) + (($y * $x) % 3) === 0,
            6 => ((($y * $x) % 2) + (($y * $x) % 3)) % 2 === 0,
            7 => ((($y + $x) % 2) + (($y * $x) % 3)) % 2 === 0,
            default => false,
        };
    }

    /**
     * @param array<int,array<int,int>> $modules
     */
    private function drawFormat(array &$modules, int $size, int $mask): void
    {
        // EC level L = 01.
        $bits = ($this->formatBits(0b01, $mask)) & 0x7FFF;

        for ($i = 0; $i <= 5; $i++) {
            $this->setFormatModule($modules, $i, 8, ($bits >> $i) & 1);
        }
        $this->setFormatModule($modules, 7, 8, ($bits >> 6) & 1);
        $this->setFormatModule($modules, 8, 8, ($bits >> 7) & 1);
        $this->setFormatModule($modules, 8, 7, ($bits >> 8) & 1);
        for ($i = 9; $i <= 14; $i++) {
            $this->setFormatModule($modules, 8, 14 - $i, ($bits >> $i) & 1);
        }

        for ($i = 0; $i <= 7; $i++) {
            $this->setFormatModule($modules, 8, $size - 1 - $i, ($bits >> $i) & 1);
        }
        for ($i = 8; $i <= 14; $i++) {
            $this->setFormatModule($modules, $size - 15 + $i, 8, ($bits >> $i) & 1);
        }

        // Always-dark module.
        $modules[$size - 8][8] = 1;
    }

    /**
     * @param array<int,array<int,int>> $modules
     */
    private function setFormatModule(array &$modules, int $x, int $y, int $bit): void
    {
        $modules[$y][$x] = $bit;
    }

    private function formatBits(int $ecLevel, int $mask): int
    {
        $data = ($ecLevel << 3) | $mask; // 5 bits
        $bch = $data << 10;
        $g = 0x537; // generator 10100110111
        for ($i = 4; $i >= 0; $i--) {
            if (($bch >> ($i + 10)) & 1) {
                $bch ^= $g << $i;
            }
        }
        $result = (($data << 10) | $bch) ^ 0x5412;

        return $result & 0x7FFF;
    }

    /**
     * @param array<int,array<int,int>> $modules
     */
    private function penalty(array $modules, int $size): int
    {
        $penalty = 0;

        // Rule 1: runs of 5+ same color.
        for ($y = 0; $y < $size; $y++) {
            $run = 1;
            for ($x = 1; $x < $size; $x++) {
                if ($modules[$y][$x] === $modules[$y][$x - 1]) {
                    $run++;
                } else {
                    if ($run >= 5) {
                        $penalty += 3 + ($run - 5);
                    }
                    $run = 1;
                }
            }
            if ($run >= 5) {
                $penalty += 3 + ($run - 5);
            }
        }
        for ($x = 0; $x < $size; $x++) {
            $run = 1;
            for ($y = 1; $y < $size; $y++) {
                if ($modules[$y][$x] === $modules[$y - 1][$x]) {
                    $run++;
                } else {
                    if ($run >= 5) {
                        $penalty += 3 + ($run - 5);
                    }
                    $run = 1;
                }
            }
            if ($run >= 5) {
                $penalty += 3 + ($run - 5);
            }
        }

        // Rule 2: 2x2 blocks.
        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $v = $modules[$y][$x];
                if ($v === $modules[$y][$x + 1] && $v === $modules[$y + 1][$x] && $v === $modules[$y + 1][$x + 1]) {
                    $penalty += 3;
                }
            }
        }

        // Rule 3: finder-like patterns (1011101 0000 or 0000 1011101).
        $pattern1 = [1, 0, 1, 1, 1, 0, 1];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x <= $size - 11; $x++) {
                if ($this->matchesPattern($modules, $y, $x, true, $pattern1)
                    && $this->isZeroRun($modules, $y, $x + 7, 4, true)) {
                    $penalty += 40;
                }
                if ($this->isZeroRun($modules, $y, $x, 4, true)
                    && $this->matchesPattern($modules, $y, $x + 4, true, $pattern1)) {
                    $penalty += 40;
                }
            }
        }
        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y <= $size - 11; $y++) {
                if ($this->matchesPattern($modules, $y, $x, false, $pattern1)
                    && $this->isZeroRun($modules, $y + 7, $x, 4, false)) {
                    $penalty += 40;
                }
                if ($this->isZeroRun($modules, $y, $x, 4, false)
                    && $this->matchesPattern($modules, $y + 4, $x, false, $pattern1)) {
                    $penalty += 40;
                }
            }
        }

        // Rule 4: dark/light balance.
        $dark = 0;
        foreach ($modules as $row) {
            foreach ($row as $cell) {
                if ($cell & 1) {
                    $dark++;
                }
            }
        }
        $total = $size * $size;
        $ratio = intdiv($dark * 100, $total);
        $k = abs($ratio - 50) / 5;
        $penalty += $k * 10;

        return $penalty;
    }

    /**
     * @param array<int,array<int,int>> $modules
     */
    private function matchesPattern(array $modules, int $y, int $x, bool $horizontal, array $pattern): bool
    {
        foreach ($pattern as $i => $v) {
            if ($horizontal) {
                if (! isset($modules[$y][$x + $i]) || ($modules[$y][$x + $i] & 1) !== $v) {
                    return false;
                }
            } else {
                if (! isset($modules[$y + $i][$x]) || ($modules[$y + $i][$x] & 1) !== $v) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array<int,array<int,int>> $modules
     */
    private function isZeroRun(array $modules, int $y, int $x, int $len, bool $horizontal): bool
    {
        for ($i = 0; $i < $len; $i++) {
            if ($horizontal) {
                if (! isset($modules[$y][$x + $i]) || ($modules[$y][$x + $i] & 1) !== 0) {
                    return false;
                }
            } else {
                if (! isset($modules[$y + $i][$x]) || ($modules[$y + $i][$x] & 1) !== 0) {
                    return false;
                }
            }
        }

        return true;
    }
}