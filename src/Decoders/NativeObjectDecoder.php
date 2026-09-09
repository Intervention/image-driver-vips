<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Drivers\SpecializableDecoder;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Modifiers\OrientModifier;
use Intervention\Image\Drivers\Vips\Traits\CanNormalizeSource;
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

class NativeObjectDecoder extends SpecializableDecoder implements SpecializedInterface
{
    use CanNormalizeSource;

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

        try {
            $input = $this->normalizeSource($input);
        } catch (VipsException $e) {
            throw new ImageDecoderException('Failed to normalize decoded image', previous: $e);
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
        $mediaType = $this->vipsMediaType($input);
        if ($mediaType !== null) {
            $image->origin()->setMediaType($mediaType);
        }

        return $image;
    }

    /**
     * Get decoder options for vips library according to current configuration
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

    /**
     * Return true if the source is in a state where we can safely stash
     * it for the resize-family modifiers' thumbnail* fast path.
     *
     * Skip stashing when the decoder auto-orients the image, a reopened
     * source would come back unrotated. The colour normalisation the decoder
     * applies (grayscale to sRGB, alpha band) is no obstacle, whoever reopens
     * the stash replays it, see CanNormalizeSource.
     *
     * @throws StateException
     */
    protected function isStashableSource(VipsImage $vipsImage): bool
    {
        if (
            $this->driver()->config()->autoOrientation === true
            && ($this->exifRotation($vipsImage) ?? 1) > 1
        ) {
            return false;
        }

        return true;
    }
}
