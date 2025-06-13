<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\AbstractFontProcessor;
use Intervention\Image\Exceptions\FontException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Geometry\Rectangle;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\FontInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Jcupitt\Vips\Align;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\TextWrap;

class FontProcessor extends AbstractFontProcessor
{
    /**
     * {@inheritdoc}
     *
     * @see FontProcessorInterface::boxSize()
     *
     * @throws RuntimeException
     */
    public function boxSize(string $text, FontInterface $font): SizeInterface
    {
        // no text - no box size
        if (mb_strlen($text) === 0) {
            return new Rectangle(0, 0);
        }

        $text = $this->textToVipsImage($text, $font);

        return new Rectangle(
            $text->width,
            $text->height,
        );
    }

    /**
     * Return renderable text/font combination in the specified colour as an vips image
     *
     * @throws FontException
     * @throws RuntimeException
     */
    public function textToVipsImage(
        string $text,
        FontInterface $font,
        ColorInterface $color = new Color(0, 0, 0),
    ): VipsImage {
        return VipsImage::text(
            '<span ' . $this->pangoAttributes($font, $color) . '>' . htmlspecialchars($text) . '</span>',
            [
                'fontfile' => $font->filename(),
                'font' => TrueTypeFont::fromPath($font->filename())->familyName() . ' ' . $font->size(),
                'dpi' => 72,
                'rgba' => true,
                'width' => $font->wrapWidth(),
                'wrap' => TextWrap::WORD,
                'align' => match ($font->alignment()) {
                    'center',
                    'middle' => Align::CENTRE,
                    'right' => Align::HIGH,
                    default => Align::LOW,
                },
                'spacing' => 0
            ]
        );
    }

    /**
     * Return a pango markup attribute string based on the given font and color values
     */
    private function pangoAttributes(FontInterface $font, ColorInterface $color): string
    {
        $pango_attributes = [
            'line_height' => (string) $font->lineHeight() / 1.62,
            'foreground' => $color->toHex('#'),
        ];

        // format pango attributes
        return implode(' ', array_map(function ($value, $key): string {
            return $key . '="' . $value . '"';
        }, $pango_attributes, array_keys($pango_attributes)));
    }
}
