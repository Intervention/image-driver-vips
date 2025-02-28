<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Drivers\AbstractFontProcessor;
use Intervention\Image\Exceptions\FontException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Geometry\Rectangle;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\FontInterface;
use Intervention\Image\Interfaces\SizeInterface;
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
     * Create vips text object according to given parameters
     *
     * @param string $text
     * @param FontInterface $font
     * @param null|ColorInterface $color
     * @throws RuntimeException
     * @throws FontException
     * @return VipsImage
     */
    public function textToVipsImage(string $text, FontInterface $font, ?ColorInterface $color = null): VipsImage
    {
        // VipsImage::text() can only handle certain characters as HTML entities
        $text = htmlentities($text);

        if (!is_null($color)) {
            $text = '<span foreground="' . $color->toHex('#') . '">' . $text . '</span>';
        }

        return VipsImage::text($text, [
            'fontfile' => $font->filename(),
            'font' => TrueTypeFont::fromPath($font->filename())->familyName() . ' ' . $font->size(),
            'dpi' => 72,
            'rgba' => true,
        ]);
    }
}
