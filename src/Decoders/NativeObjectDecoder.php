<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Drivers\SpecializableDecoder;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Modifiers\AlignRotationModifier;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;

class NativeObjectDecoder extends SpecializableDecoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     *
     * @throws DecoderException|RuntimeException
     */
    public function decode(mixed $input): ImageInterface|ColorInterface
    {
        if (!is_object($input)) {
            throw new DecoderException('Unable to decode input');
        }

        if (!($input instanceof VipsImage)) {
            throw new DecoderException('Unable to decode input');
        }

        // build image instance
        $image = new Image(
            $this->driver(),
            new Core($input)
        );

        // auto-rotate
        if ($this->driver()->config()->autoOrientation === true && $this->exifRotation($input) > 1) {
            $image->modify(new AlignRotationModifier());
        }

        // set media type on origin
        if ($mediaType = $this->vipsMediaType($input)) {
            $image->origin()->setMediaType($mediaType);
        }

        return $image;
    }

    /**
     * Get options for vips library according to current configuration
     */
    protected function stringOptions(): string
    {
        $options = '';

        if ($this->driver()->config()->decodeAnimation === true) {
            $options = 'n=-1';
        }

        return $options;
    }

    /**
     * Return media type of given vips image instance
     */
    protected function vipsMediaType(VipsImage $vips): ?MediaType
    {
        try {
            $loader = $vips->get('vips-loader');
        } catch (VipsException) {
            return null;
        }

        $result = preg_match("/^(?P<loader>.+)load(_.+)?$/", $loader, $matches);

        if ($result !== 1) {
            return null;
        }

        return match ($matches['loader']) {
            'gif' => MediaType::IMAGE_GIF,
            'heif' => MediaType::IMAGE_HEIF,
            'jp2k' => MediaType::IMAGE_JP2,
            'jpeg' => MediaType::IMAGE_JPEG,
            'png' => MediaType::IMAGE_PNG,
            'tiff' => MediaType::IMAGE_TIFF,
            'webp' => MediaType::IMAGE_WEBP,
            default => null
        };
    }

    /**
     * Return the exif rotation of the given image or null if there isn't any
     */
    protected function exifRotation(VipsImage $vips): ?int
    {
        if (!in_array('exif-ifd0-Orientation', $vips->getFields())) {
            return null;
        }

        try {
            $orientation = substr($vips->get('exif-ifd0-Orientation'), 0, 1);
        } catch (VipsException) {
            return null;
        }

        if (!is_numeric($orientation)) {
            return null;
        }

        return (int) $orientation;
    }
}
