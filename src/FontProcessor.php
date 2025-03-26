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

class FontProcessor extends AbstractFontProcessor
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     * @see FontProcessorInterface::boxSize()
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
     * @param string $text
     * @param FontInterface $font
     * @param ColorInterface $color
     * @throws FontException
     * @throws RuntimeException
     * @return VipsImage
     */
    public function textToVipsImage(
        string $text,
        FontInterface $font,
        ColorInterface $color = new Color(0, 0, 0),
    ): VipsImage {
        // TODO: implement line spacing

        // @font size 24:
        // ---------------
        // 1 -> -15
        // 1.25 -> -10
        // 2 -> 7
        // 3 -> 18

        // @font size 80:
        // ---------------
        // 1 -> -35
        // 1.25 -> -30
        // 2 -> 35
        // 3 -> 110

        // @font size 100:
        // ---------------
        // 1 -> -45
        // 1.25 -> -35
        // 2 -> -10
        // 3 -> 55

        // @font size 120:
        // ---------------
        // 1 -> -55
        // 1.25 -> -30
        // 2 -> 35
        // 3 -> 110

        // leading 168

        // 1 point (computer) 1.3333333333 pixel (X)
        // typicall like font size times 1.2.

        return VipsImage::text(
            '<span foreground="' . $color->toHex('#') . '">' . htmlentities($text) . '</span>',
            [
                'fontfile' => $font->filename(),
                'font' => TrueTypeFont::fromPath($font->filename())->familyName() . ' ' . $font->size(),
                'dpi' => 72,
                'rgba' => true,
                'width' => $font->wrapWidth(),
                'align' => match ($font->alignment()) {
                    'center',
                    'middle' => Align::CENTRE,
                    'right' => Align::HIGH,
                    default => Align::LOW,
                },
                'spacing' => 0 // add value as pixel to each line
            ]
        );
    }
}
