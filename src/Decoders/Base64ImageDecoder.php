<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Traits\CanDetectImageSources;

class Base64ImageDecoder extends BinaryImageDecoder
{
    use CanDetectImageSources;

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::supports()
     */
    public function supports(mixed $input): bool
    {
        return $this->couldBeBase64Data($input);
    }

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     *
     * @throws InvalidArgumentException
     * @throws ImageDecoderException
     * @throws DecoderException
     * @throws StateException
     */
    public function decode(mixed $input): ImageInterface
    {
        $binary = base64_decode($input);

        if ($binary == false) {
            throw new ImageDecoderException('Failed to decode Base64-encoded string');
        }

        try {
            return parent::decode($binary);
        } catch (ImageDecoderException) {
            throw new ImageDecoderException('Base64-encoded data contains unsupported image type');
        }
    }
}
