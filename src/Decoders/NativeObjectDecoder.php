<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Drivers\SpecializableDecoder;
use Intervention\Image\Drivers\Vips\Core;
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

        // auto-rotate
        if ($this->driver()->config()->autoOrientation === true) {
            $input = $input->autorot();
        }

        // build image instance
        $image = new Image(
            $this->driver(),
            new Core($input)
        );

        // set media type on origin
        if ($mediaType = $this->vipsMediaType($input)) {
            $image->origin()->setMediaType($mediaType);
        }

        return $image;
    }

    /**
     * Get options for vips library according to current configuration
     *
     * @return string
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
     *
     * @param VipsImage $vips
     * @return null|MediaType
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
}
