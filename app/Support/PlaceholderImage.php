<?php

declare(strict_types=1);

namespace App\Support;

use GdImage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Draws illustration placeholders for the demonstration data.
 *
 * Generated on the machine rather than shipped in the repository: binary
 * fixtures bloat a clone, and no stock photo can be committed without knowing
 * its licence. Each scene is a handful of GD primitives — silhouettes in a
 * themed palette on a soft canvas — so the catalogue and the training shelves
 * read as illustrated cards rather than as failed image loads. GD is already
 * a required extension.
 */
final class PlaceholderImage
{
    /**
     * Whether the machine can draw a placeholder at all.
     *
     * GD is a required extension, but a missing one should be reported as
     * such — not surface as "call to undefined function" from the middle of
     * a seeder.
     */
    public static function isSupported(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * The scene keys the seeder can request, with their caption. Keeping the
     * list here means the seeder picks by product key and every unknown key
     * still degrades to the generic placeholder instead of failing.
     *
     * @return array<string, string>
     */
    public static function scenes(): array
    {
        return [
            'manioc' => 'Manioc',
            'plantain' => 'Régime de plantain',
            'tomate' => 'Tomates',
            'ananas' => 'Ananas',
            'cafe' => 'Café',
            'miel' => 'Miel',
            'poulet' => 'Volaille',
            'avocat' => 'Avocats',
            'mais' => 'Maïs',
            'arachide' => 'Arachide',
            'formation-compostage' => 'Compostage',
            'formation-irrigation' => 'Irrigation',
            'formation-cacao' => 'Cacaoyère',
            'formation-conservation' => 'Conservation',
            'generic' => 'AgriTech',
        ];
    }

    /**
     * Draw a square-ish product illustration (catalogue cards, gallery).
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb  Canvas colour, used when the
     *                                              scene is unknown.
     */
    public static function png(string $scene, array $rgb = [34, 110, 62], int $width = 800, int $height = 600): string
    {
        if (! self::isSupported()) {
            throw new RuntimeException(
                'L\'extension PHP GD n\'est pas activée : décommentez "extension=gd" dans php.ini puis redémarrez.',
            );
        }

        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('The placeholder size must be at least one pixel on each side.');
        }

        [$red, $green, $blue] = $rgb;

        if ($red < 0 || $red > 255 || $green < 0 || $green > 255 || $blue < 0 || $blue > 255) {
            throw new InvalidArgumentException('Each colour channel must be between 0 and 255.');
        }

        $canvas = imagecreatetruecolor($width, $height);

        if (! $canvas instanceof GdImage) {
            throw new RuntimeException('GD could not create the canvas.');
        }

        $painter = new self($canvas);
        $painter->paintScene($scene, $rgb);
        $painter->caption(self::scenes()[$scene] ?? 'AgriTech');

        ob_start();
        imagepng($canvas);
        $bytes = (string) ob_get_clean();

        imagedestroy($canvas);

        return $bytes;
    }

    /**
     * Draw a 16:9 training cover (card thumbnails, page hero).
     */
    public static function cover(string $scene): string
    {
        return self::png($scene, [34, 110, 62], 1280, 720);
    }

    private function __construct(private readonly GdImage $canvas) {}

    /**
     * Paint the requested scene over a two-tone canvas. The RGB triple is
     * validated by the caller (png()); every channel is clamped again here
     * before it reaches GD.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function paintScene(string $scene, array $rgb): void
    {
        $w = imagesx($this->canvas);
        $h = imagesy($this->canvas);

        // Soft two-tone canvas: warm ground line under every scene.
        $sky = $this->rgb($rgb);
        $ground = $this->rgb([
            self::clampChannel($rgb[0] + 18),
            self::clampChannel($rgb[1] + 16),
            self::clampChannel($rgb[2] + 14),
        ]);
        imagefilledrectangle($this->canvas, 0, 0, $w - 1, $h - 1, $sky);
        imagefilledrectangle($this->canvas, 0, (int) ($h * 0.78), $w - 1, $h - 1, $ground);

        match ($scene) {
            'manioc' => $this->cassava($w, $h),
            'plantain' => $this->bunch($w, $h),
            'tomate' => $this->tomatoes($w, $h),
            'ananas' => $this->pineapple($w, $h),
            'cafe' => $this->coffee($w, $h),
            'miel' => $this->honey($w, $h),
            'poulet' => $this->hen($w, $h),
            'avocat' => $this->avocados($w, $h),
            'mais' => $this->corn($w, $h),
            'arachide' => $this->peanuts($w, $h),
            'formation-compostage' => $this->compost($w, $h),
            'formation-irrigation' => $this->irrigation($w, $h),
            'formation-cacao' => $this->cacao($w, $h),
            'formation-conservation' => $this->sacks($w, $h),
            default => $this->diagonalShade($w, $h),
        };
    }

    // ------------------------------------------------------------------
    // Scenes. Each is deliberately simple: silhouettes built from ellipses,
    // rectangles and arcs, drawn large so they survive a 120px card.
    // ------------------------------------------------------------------

    private function cassava(int $w, int $h): void
    {
        // A bunch of tapering roots fanned out from the crown.
        $crownX = (int) ($w * 0.5);
        $crownY = (int) ($h * 0.30);
        $brown = $this->color(139, 94, 60);
        $light = $this->color(189, 144, 104);

        $angles = [-3, -2, -1, 0, 1, 2, 3];
        foreach ($angles as $i => $angle) {
            $tipX = $crownX + (int) ($angle * $w * 0.09);
            $tipY = $crownY + (int) ($h * 0.52);
            $steps = 26;
            for ($step = 0; $step <= $steps; $step++) {
                $t = $step / $steps;
                $x = (int) ($crownX + ($tipX - $crownX) * $t);
                $y = (int) ($crownY + ($tipY - $crownY) * $t);
                $r = (int) ((1 - $t) * $w * 0.035 + $w * 0.012);
                imagefilledellipse($this->canvas, $x, $y, $r * 2, (int) ($r * 1.4), $i % 2 === 0 ? $brown : $light);
            }
        }

        // Leafy stems above the crown.
        $leaf = $this->color(38, 92, 50);
        foreach ([-2, -1, 0, 1, 2] as $angle) {
            $stemX = $crownX + (int) ($angle * $w * 0.05);
            imagefilledrectangle($this->canvas, $stemX - 3, $crownY - (int) ($h * 0.16), $stemX + 3, $crownY, $leaf);
            imagefilledellipse($this->canvas, $stemX, $crownY - (int) ($h * 0.18), (int) ($w * 0.11), (int) ($h * 0.09), $leaf);
        }
    }

    private function bunch(int $w, int $h): void
    {
        // A plantain bunch: two rows of curving fingers around a stalk.
        $green = $this->color(58, 112, 46);
        $dark = $this->color(44, 88, 36);
        $stalkX = (int) ($w * 0.5);
        $stalkTop = (int) ($h * 0.12);
        imagefilledrectangle($this->canvas, $stalkX - 10, $stalkTop, $stalkX + 10, (int) ($h * 0.62), $dark);

        $rows = [[0.30, 0.44, $green], [0.48, 0.60, $dark]];
        foreach ($rows as [$yFactor, $xFactor, $tone]) {
            foreach ([-4, -3, -2, -1, 0, 1, 2, 3, 4] as $finger) {
                $cx = $stalkX + (int) ($finger * $w * 0.085);
                $cy = (int) ($h * $yFactor) + abs($finger) * 4;
                imagefilledellipse($this->canvas, $cx, $cy, (int) ($w * 0.075), (int) ($h * $xFactor * 0.5), $tone);
            }
        }
    }

    private function tomatoes(int $w, int $h): void
    {
        // A crate of tomatoes seen from the front, a few on top.
        $wood = $this->color(154, 108, 66);
        $crateY = (int) ($h * 0.42);
        imagefilledrectangle($this->canvas, (int) ($w * 0.18), $crateY, (int) ($w * 0.82), (int) ($h * 0.82), $wood);
        $slat = $this->color(120, 82, 48);
        for ($i = 1; $i <= 2; $i++) {
            imagefilledrectangle(
                $this->canvas,
                (int) ($w * 0.18),
                $crateY + (int) ($h * 0.13) * $i,
                (int) ($w * 0.82),
                $crateY + (int) ($h * 0.13) * $i + 6,
                $slat,
            );
        }

        $red = $this->color(200, 62, 48);
        $dark = $this->color(168, 48, 36);
        $tops = [[0.30, 0.40], [0.44, 0.36], [0.58, 0.40], [0.70, 0.37]];
        foreach ($tops as $i => [$xFactor, $yFactor]) {
            imagefilledellipse(
                $this->canvas,
                (int) ($w * $xFactor),
                (int) ($h * $yFactor),
                (int) ($w * 0.14),
                (int) ($w * 0.14),
                $i % 2 === 0 ? $red : $dark,
            );
        }
        // Small green calyxes.
        $leaf = $this->color(52, 104, 44);
        foreach ($tops as [$xFactor, $yFactor]) {
            imagefilledellipse(
                $this->canvas,
                (int) ($w * $xFactor),
                (int) ($h * $yFactor) - (int) ($w * 0.07),
                (int) ($w * 0.05),
                (int) ($h * 0.03),
                $leaf,
            );
        }
    }

    private function pineapple(int $w, int $h): void
    {
        $body = $this->color(196, 138, 40);
        $crown = $this->color(46, 104, 48);
        $cx = (int) ($w * 0.5);

        imagefilledellipse($this->canvas, $cx, (int) ($h * 0.55), (int) ($w * 0.34), (int) ($h * 0.44), $body);

        // Diamond cross-hatch reads as pineapple skin at card size.
        $skin = $this->color(160, 108, 28);
        for ($y = 0; $y < 5; $y++) {
            for ($x = 0; $x < 4; $x++) {
                $dx = $cx - (int) ($w * 0.11) + $x * (int) ($w * 0.073) + ($y % 2) * (int) ($w * 0.036);
                $dy = (int) ($h * 0.40) + $y * (int) ($h * 0.07);
                imagefilledellipse($this->canvas, $dx, $dy, 8, 8, $skin);
            }
        }

        for ($i = -3; $i <= 3; $i++) {
            $tipX = $cx + $i * (int) ($w * 0.045);
            $tipY = (int) ($h * 0.30) - abs($i) * (int) ($h * 0.02);
            imagefilledpolygon($this->canvas, [$cx + $i * 6 - 5, (int) ($h * 0.34), $cx + $i * 6 + 5, (int) ($h * 0.34), $tipX, $tipY], $crown);
        }
    }

    private function coffee(int $w, int $h): void
    {
        // A sack of coffee beans with a few beans spilled in front.
        $sack = $this->color(158, 118, 70);
        imagefilledrectangle($this->canvas, (int) ($w * 0.26), (int) ($h * 0.34), (int) ($w * 0.74), (int) ($h * 0.80), $sack);
        imagefilledpolygon(
            $this->canvas,
            [(int) ($w * 0.26), (int) ($h * 0.34), (int) ($w * 0.74), (int) ($h * 0.34), (int) ($w * 0.5), (int) ($h * 0.22)],
            $this->color(132, 96, 56),
        );
        // Stitched seam.
        $thread = $this->color(96, 70, 40);
        for ($x = (int) ($w * 0.28); $x < $w * 0.72; $x += 14) {
            imagefilledrectangle($this->canvas, $x, (int) ($h * 0.30), $x + 7, (int) ($h * 0.30) + 4, $thread);
        }

        $bean = $this->color(94, 60, 32);
        foreach ([[0.38, 0.84], [0.47, 0.87], [0.56, 0.85], [0.63, 0.88]] as [$xFactor, $yFactor]) {
            imagefilledellipse($this->canvas, (int) ($w * $xFactor), (int) ($h * $yFactor), 26, 18, $bean);
        }
    }

    private function honey(int $w, int $h): void
    {
        $h = (int) $h;
        // A jar with a wooden dipper and a honeycomb motif.
        $glass = $this->color(238, 178, 60);
        imagefilledrectangle($this->canvas, (int) ($w * 0.36), (int) ($h * 0.40), (int) ($w * 0.64), (int) ($h * 0.78), $glass);
        $lid = $this->color(122, 82, 44);
        imagefilledrectangle($this->canvas, (int) ($w * 0.34), (int) ($h * 0.33), (int) ($w * 0.66), (int) ($h * 0.40), $lid);
        $drip = $this->color(252, 202, 96);
        imagefilledellipse($this->canvas, (int) ($w * 0.5), (int) ($h * 0.62), (int) ($w * 0.18), (int) ($h * 0.20), $drip);

        $comb = $this->color(196, 138, 40);
        for ($row = 0; $row < 3; $row++) {
            for ($cell = 0; $cell < 4; $cell++) {
                $cx = (int) ($w * 0.10) + $cell * (int) ($w * 0.06) + ($row % 2) * (int) ($w * 0.03);
                $cy = (int) ($h * 0.20) + $row * (int) ($h * 0.055);
                imagepolygon($this->canvas, [
                    $cx, $cy - 14, $cx + 12, $cy - 7, $cx + 12, $cy + 7,
                    $cx, $cy + 14, $cx - 12, $cy + 7, $cx - 12, $cy - 7,
                ], $comb);
            }
        }
    }

    private function hen(int $w, int $h): void
    {
        $body = $this->color(214, 196, 158);
        $wing = $this->color(184, 160, 120);
        $comb = $this->color(200, 62, 48);
        $beak = $this->color(232, 168, 40);

        imagefilledellipse($this->canvas, (int) ($w * 0.46), (int) ($h * 0.55), (int) ($w * 0.36), (int) ($h * 0.30), $body);
        imagefilledellipse($this->canvas, (int) ($w * 0.40), (int) ($h * 0.56), (int) ($w * 0.20), (int) ($h * 0.17), $wing);
        imagefilledellipse($this->canvas, (int) ($w * 0.64), (int) ($h * 0.36), (int) ($w * 0.13), (int) ($h * 0.13), $body);
        imagefilledellipse($this->canvas, (int) ($w * 0.66), (int) ($h * 0.29), 16, 10, $comb);
        imagefilledpolygon($this->canvas, [(int) ($w * 0.70), (int) ($h * 0.35), (int) ($w * 0.76), (int) ($h * 0.37), (int) ($w * 0.70), (int) ($h * 0.39)], $beak);
        // Legs and ground line.
        $leg = $this->color(190, 132, 60);
        imagefilledrectangle($this->canvas, (int) ($w * 0.42), (int) ($h * 0.68), (int) ($w * 0.42) + 5, (int) ($h * 0.80), $leg);
        imagefilledrectangle($this->canvas, (int) ($w * 0.52), (int) ($h * 0.68), (int) ($w * 0.52) + 5, (int) ($h * 0.80), $leg);
    }

    private function avocados(int $w, int $h): void
    {
        // Two whole avocados and one cut half.
        $skin = $this->color(58, 82, 44);
        $flesh = $this->color(180, 204, 96);
        $pit = $this->color(140, 92, 40);

        imagefilledellipse($this->canvas, (int) ($w * 0.38), (int) ($h * 0.55), (int) ($w * 0.24), (int) ($h * 0.38), $skin);
        imagefilledellipse($this->canvas, (int) ($w * 0.60), (int) ($h * 0.58), (int) ($w * 0.22), (int) ($h * 0.34), $skin);
        imagefilledellipse($this->canvas, (int) ($w * 0.78), (int) ($h * 0.52), (int) ($w * 0.22), (int) ($h * 0.30), $flesh);
        imagefilledellipse($this->canvas, (int) ($w * 0.78), (int) ($h * 0.52), (int) ($w * 0.09), (int) ($w * 0.09), $pit);
    }

    private function corn(int $w, int $h): void
    {
        // An ear of corn with husk leaves.
        $kernel = $this->color(226, 178, 52);
        $husk = $this->color(94, 132, 52);
        $cx = (int) ($w * 0.5);

        imagefilledellipse($this->canvas, $cx, (int) ($h * 0.46), (int) ($w * 0.20), (int) ($h * 0.42), $kernel);
        for ($y = 0; $y < 7; $y++) {
            for ($x = -1; $x <= 1; $x++) {
                imagefilledellipse(
                    $this->canvas,
                    $cx + $x * (int) ($w * 0.045),
                    (int) ($h * 0.30) + $y * (int) ($h * 0.052),
                    10, 10,
                    $this->color(200, 152, 40),
                );
            }
        }
        imagefilledpolygon($this->canvas, [$cx - (int) ($w * 0.09), (int) ($h * 0.68), $cx - (int) ($w * 0.01), (int) ($h * 0.68), $cx - (int) ($w * 0.13), (int) ($h * 0.86)], $husk);
        imagefilledpolygon($this->canvas, [$cx + (int) ($w * 0.01), (int) ($h * 0.68), $cx + (int) ($w * 0.09), (int) ($h * 0.68), $cx + (int) ($w * 0.13), (int) ($h * 0.86)], $husk);
    }

    private function peanuts(int $w, int $h): void
    {
        // Peanut shells in a bowl.
        $bowl = $this->color(168, 118, 70);
        imagefilledellipse($this->canvas, (int) ($w * 0.5), (int) ($h * 0.60), (int) ($w * 0.52), (int) ($h * 0.24), $bowl);
        $shell = $this->color(206, 168, 110);
        $spots = [[0.36, 0.55], [0.45, 0.52], [0.54, 0.55], [0.62, 0.53], [0.50, 0.58], [0.58, 0.58]];
        foreach ($spots as $i => [$xFactor, $yFactor]) {
            $x = (int) ($w * $xFactor);
            $y = (int) ($h * $yFactor);
            imagefilledellipse($this->canvas, $x, $y, 34, 22, $shell);
            // Waist notch so the blob reads as a peanut.
            imagefilledellipse($this->canvas, $x, $y, 10, 12, $this->color(168, 130, 76));
            if ($i === 0) {
                continue;
            }
        }
    }

    private function compost(int $w, int $h): void
    {
        // A compost heap with layered strata and a pitchfork.
        $layers = [
            [0.50, $this->color(122, 88, 48)],
            [0.58, $this->color(88, 110, 52)],
            [0.66, $this->color(142, 104, 58)],
            [0.74, $this->color(70, 96, 46)],
        ];
        foreach ($layers as [$yFactor, $tone]) {
            imagefilledellipse($this->canvas, (int) ($w * 0.48), (int) ($h * $yFactor), (int) ($w * 0.5), (int) ($h * 0.12), $tone);
        }
        // Steam wisps.
        $steam = $this->color(255, 255, 255, 90);
        foreach ([[0.40, 0.22], [0.50, 0.16], [0.60, 0.24]] as [$xFactor, $yFactor]) {
            imagefilledellipse($this->canvas, (int) ($w * $xFactor), (int) ($h * $yFactor), 26, 14, $steam);
        }
    }

    private function irrigation(int $w, int $h): void
    {
        // A drip line over a row of seedlings, droplets mid-fall.
        $pipe = $this->color(84, 118, 138);
        imagefilledrectangle($this->canvas, (int) ($w * 0.14), (int) ($h * 0.28), (int) ($w * 0.86), (int) ($h * 0.28) + 10, $pipe);
        $water = $this->color(96, 152, 190);
        foreach ([0.22, 0.38, 0.54, 0.70, 0.84] as $xFactor) {
            imagefilledellipse($this->canvas, (int) ($w * $xFactor), (int) ($h * 0.38), 8, 14, $water);
            imagefilledellipse($this->canvas, (int) ($w * $xFactor), (int) ($h * 0.48), 8, 14, $water);
        }
        // Seedling row.
        $leaf = $this->color(58, 118, 54);
        foreach ([0.24, 0.40, 0.56, 0.72, 0.86] as $xFactor) {
            $x = (int) ($w * $xFactor);
            imagefilledrectangle($this->canvas, $x - 3, (int) ($h * 0.68), $x + 3, (int) ($h * 0.80), $leaf);
            imagefilledellipse($this->canvas, $x - 8, (int) ($h * 0.66), 20, 12, $leaf);
            imagefilledellipse($this->canvas, $x + 8, (int) ($h * 0.66), 20, 12, $leaf);
        }
    }

    private function cacao(int $w, int $h): void
    {
        // A cacao pod on a branch with leaves.
        $branch = $this->color(110, 78, 46);
        imagefilledrectangle($this->canvas, 0, (int) ($h * 0.20), $w, (int) ($h * 0.20) + 12, $branch);
        $pod = $this->color(198, 122, 44);
        imagefilledellipse($this->canvas, (int) ($w * 0.42), (int) ($h * 0.52), (int) ($w * 0.17), (int) ($h * 0.40), $pod);
        // Pod ridges.
        $ridge = $this->color(166, 98, 32);
        for ($i = -2; $i <= 2; $i++) {
            imagefilledellipse(
                $this->canvas,
                (int) ($w * 0.42) + $i * (int) ($w * 0.028),
                (int) ($h * 0.52),
                7,
                (int) ($h * 0.36),
                $ridge,
            );
        }
        $leaf = $this->color(52, 106, 48);
        foreach ([[0.60, 0.28], [0.68, 0.40], [0.30, 0.26]] as [$xFactor, $yFactor]) {
            imagefilledellipse($this->canvas, (int) ($w * $xFactor), (int) ($h * $yFactor), (int) ($w * 0.14), (int) ($h * 0.07), $leaf);
        }
    }

    private function sacks(int $w, int $h): void
    {
        // Two stacked storage sacks with a label patch.
        $sack = $this->color(158, 122, 74);
        $shade = $this->color(132, 100, 60);
        imagefilledrectangle($this->canvas, (int) ($w * 0.22), (int) ($h * 0.52), (int) ($w * 0.58), (int) ($h * 0.82), $sack);
        imagefilledrectangle($this->canvas, (int) ($w * 0.44), (int) ($h * 0.26), (int) ($w * 0.78), (int) ($h * 0.52), $shade);
        // Tied ears.
        imagefilledpolygon($this->canvas, [(int) ($w * 0.38), (int) ($h * 0.52), (int) ($w * 0.44), (int) ($h * 0.52), (int) ($w * 0.41), (int) ($h * 0.44)], $sack);
        imagefilledpolygon($this->canvas, [(int) ($w * 0.59), (int) ($h * 0.26), (int) ($w * 0.65), (int) ($h * 0.26), (int) ($w * 0.62), (int) ($h * 0.18)], $shade);
    }

    private function diagonalShade(int $w, int $h): void
    {
        // Generic fallback: the original diagonal band placeholder.
        imagefilledpolygon(
            $this->canvas,
            [0, $h, $w, 0, $w, (int) ($h * 0.35), 0, $h],
            $this->color(255, 255, 255, 110),
        );
    }

    private function caption(string $label): void
    {
        $w = imagesx($this->canvas);
        $h = imagesy($this->canvas);

        $text = mb_strtoupper(mb_substr($label, 0, 24));
        $font = 5;
        $textWidth = imagefontwidth($font) * mb_strlen($text);
        $textHeight = imagefontheight($font);
        $padX = 10;

        // White pill behind the caption so it stays legible on any scene.
        $pill = $this->color(255, 255, 255, 100);
        imagefilledrectangle(
            $this->canvas,
            (int) (($w - $textWidth) / 2) - $padX,
            (int) (($h - $textHeight) / 2) - 6,
            (int) (($w + $textWidth) / 2) + $padX,
            (int) (($h + $textHeight) / 2) + 6,
            $pill,
        );

        imagestring(
            $this->canvas,
            $font,
            (int) (($w - $textWidth) / 2),
            (int) (($h - $textHeight) / 2),
            $text,
            $this->color(255, 255, 255),
        );
    }

    /**
     * A brighter variant of a channel, capped to the valid GD range.
     *
     * @return int<0, 255>
     */
    private static function clampChannel(int $value): int
    {
        return min(255, max(0, $value));
    }

    /**
     * Allocate the canvas colour from an RGB triple of channel values.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function rgb(array $rgb): int
    {
        return $this->color(
            self::clampChannel($rgb[0]),
            self::clampChannel($rgb[1]),
            self::clampChannel($rgb[2]),
        );
    }

    /**
     * GD refuses a colour when the palette is full; drawing with the `false`
     * it returns would paint an arbitrary palette entry instead of failing.
     *
     * @param  int<0, 255>  $red
     * @param  int<0, 255>  $green
     * @param  int<0, 255>  $blue
     * @param  int<0, 127>  $alpha
     */
    private function color(int $red, int $green, int $blue, int $alpha = 0): int
    {
        $color = $alpha === 0
            ? imagecolorallocate($this->canvas, $red, $green, $blue)
            : imagecolorallocatealpha($this->canvas, $red, $green, $blue, $alpha);

        if ($color === false) {
            throw new RuntimeException('GD could not allocate a colour.');
        }

        return $color;
    }
}
