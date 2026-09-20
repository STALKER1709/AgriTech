<?php

declare(strict_types=1);

namespace App\Support;

use GdImage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Draws a simple placeholder image, for the demonstration data.
 *
 * Generated on the machine rather than shipped in the repository: binary
 * fixtures bloat a clone, and no stock photo can be committed without knowing
 * its licence. GD is already a required extension.
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
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    public static function png(string $label, array $rgb, int $width = 800, int $height = 600): string
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

        $background = self::allocate($canvas, $red, $green, $blue);
        $ink = self::allocate($canvas, 255, 255, 255);

        // A diagonal band, so the placeholder reads as deliberate rather than
        // as a failed image load.
        $shade = self::allocate($canvas, 255, 255, 255, 110);

        imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $background);
        imagefilledpolygon($canvas, [0, $height, $width, 0, $width, (int) ($height * 0.35), 0, $height], $shade);

        $text = mb_strtoupper(mb_substr($label, 0, 24));
        $font = 5;
        $textWidth = imagefontwidth($font) * mb_strlen($text);
        $textHeight = imagefontheight($font);

        imagestring(
            $canvas,
            $font,
            (int) (($width - $textWidth) / 2),
            (int) (($height - $textHeight) / 2),
            $text,
            $ink,
        );

        ob_start();
        imagepng($canvas);
        $bytes = (string) ob_get_clean();

        imagedestroy($canvas);

        return $bytes;
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
    private static function allocate(GdImage $canvas, int $red, int $green, int $blue, int $alpha = 0): int
    {
        $color = $alpha === 0
            ? imagecolorallocate($canvas, $red, $green, $blue)
            : imagecolorallocatealpha($canvas, $red, $green, $blue, $alpha);

        if ($color === false) {
            throw new RuntimeException('GD could not allocate a colour.');
        }

        return $color;
    }
}
