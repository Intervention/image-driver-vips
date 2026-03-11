<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Alignment;
use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\AbstractFontProcessor;
use Intervention\Image\Exceptions\DirectoryNotFoundException;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\FileNotFoundException;
use Intervention\Image\Exceptions\FileNotReadableException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
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
     * @throws InvalidArgumentException
     * @throws DriverException
     * @throws DirectoryNotFoundException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws StreamException
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
     * @throws InvalidArgumentException
     * @throws DriverException
     * @throws DirectoryNotFoundException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws StreamException
     */
    public function textToVipsImage(
        string $text,
        FontInterface $font,
        ColorInterface $color = new Color(0, 0, 0),
    ): VipsImage {
        return VipsImage::text(
            '<span ' . $this->pangoAttributes($font, $color) . '>' . htmlspecialchars($text) . '</span>',
            [
                'fontfile' => $font->filepath(),
                'font' => TrueTypeFont::createFromPath($font->filepath())->familyName() . ' ' . $font->size(),
                'dpi' => 72,
                'rgba' => true,
                'width' => $font->wrapWidth(),
                'wrap' => TextWrap::WORD,
                'align' => match ($font->alignmentHorizontal()) {
                    Alignment::CENTER,
                    Alignment::RIGHT => Align::HIGH,
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
        $pangoAttributes = [
            'line_height' => (string) $font->lineHeight() / 1.62,
            'foreground' => $color->toHex(true),
        ];

        // format pango attributes
        return implode(' ', array_map(function ($value, $key): string {
            return $key . '="' . $value . '"';
        }, $pangoAttributes, array_keys($pangoAttributes)));
    }
}
