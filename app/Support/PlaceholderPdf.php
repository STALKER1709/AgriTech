<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A one-page PDF, assembled by hand.
 *
 * The demo has to ship real training modules: a reader screen with nothing to
 * read proves nothing, and the entitlement check in
 * TrainingContentController is only exercised when there is a file behind it.
 * A PDF is the one document format that can be produced from pure PHP with no
 * extension, no binary and no network — which is exactly the constraint the
 * project sets itself.
 *
 * It is deliberately plain: a title, a few lines, and the AgriTech footer. It
 * stands in for the document a farmer would upload, and says so on the page
 * rather than pretending to be course material.
 */
final class PlaceholderPdf
{
    /**
     * Helvetica at 12pt, which is what the content stream below selects.
     */
    private const string FONT = 'Helvetica';

    /**
     * @param  array<int, string>  $lines  the body, one entry per paragraph
     */
    public static function render(string $title, array $lines): string
    {
        $content = self::contentStream($title, $lines);

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>',
            4 => '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /'.self::FONT.'-Bold /Encoding /WinAnsiEncoding >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /'.self::FONT.' /Encoding /WinAnsiEncoding >>',
        ];

        return self::assemble($objects);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private static function contentStream(string $title, array $lines): string
    {
        $stream = "BT\n/F1 18 Tf\n60 760 Td\n".self::text($title)." Tj\nET\n";

        $y = 720;

        foreach ($lines as $line) {
            foreach (self::wrap($line) as $fragment) {
                $stream .= "BT\n/F2 12 Tf\n60 ".$y." Td\n".self::text($fragment)." Tj\nET\n";
                $y -= 18;
            }

            $y -= 8;
        }

        $stream .= "BT\n/F2 9 Tf\n60 60 Td\n".self::text('AgriTech — document de démonstration')." Tj\nET\n";

        return $stream;
    }

    /**
     * Break a paragraph at roughly the page width. Helvetica at 12pt fits
     * about 85 characters between the margins; counting characters is coarse
     * but needs no font metrics.
     *
     * @return array<int, string>
     */
    private static function wrap(string $line): array
    {
        $wrapped = wordwrap($line, 85, "\n", true);

        return explode("\n", $wrapped);
    }

    /**
     * A PDF string literal, in the encoding the fonts above declare.
     */
    private static function text(string $value): string
    {
        $encoded = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

        return '('.str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded).')';
    }

    /**
     * Lay the objects out and build the cross-reference table, whose offsets
     * are what makes the file readable.
     *
     * @param  array<int, string>  $objects
     */
    private static function assemble(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $start = strlen($pdf);
        $count = count($objects) + 1;

        $pdf .= "xref\n0 ".$count."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".$count." /Root 1 0 R >>\nstartxref\n".$start."\n%%EOF\n";

        return $pdf;
    }
}
