<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Drivers\SpecializableDecoder;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Modifiers\OrientModifier;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Image;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\MediaType;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

class NativeObjectDecoder extends SpecializableDecoder implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::supports()
     */
    public function supports(mixed $input): bool
    {
        return $input instanceof VipsImage;
    }

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     *
     * @throws InvalidArgumentException
     * @throws ImageDecoderException
     * @throws StateException
     */
    public function decode(mixed $input): ImageInterface
    {
        if (!is_object($input)) {
            throw new InvalidArgumentException('Image source must be of type ' . VipsImage::class);
        }

        if (!($input instanceof VipsImage)) {
            throw new InvalidArgumentException('Image source must be of type ' . VipsImage::class);
        }

        if (in_array($input->interpretation, [Interpretation::B_W, Interpretation::GREY16])) {
            $input = $input->icc_transform(Interpretation::SRGB); // normalize to srgb
        }

        if ($input->interpretation === Interpretation::SRGB && $input->bands === 3) {
            $input = $input->bandjoin_const(255); // add alpha channel
        }

        // build image instance
        $image = new Image($this->driver(), new Core($input));

        // auto-rotate
        if ($this->driver()->config()->autoOrientation === true && $this->exifRotation($input) > 1) {
            try {
                $image->modify(new OrientModifier());
            } catch (ModifierException $e) {
                throw new ImageDecoderException('Failed to auto-rotate image in decoding process', previous: $e);
            }
        }

        // set media type on origin
        if ($mediaType = $this->vipsMediaType($input)) {
            $image->origin()->setMediaType($mediaType);
        }

        return $image;
    }

    /**
     * Get options for vips library according to current configuration
     *
     * @throws StateException
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
        if (!in_array('orientation', $vips->getFields())) {
            return null;
        }

        try {
            $orientation = $vips->get('orientation');
        } catch (VipsException) {
            return null;
        }

        return is_int($orientation) ? $orientation : null;
    }
}
