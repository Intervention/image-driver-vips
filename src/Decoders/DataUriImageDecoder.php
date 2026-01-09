<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\DataUri;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Traits\CanDetectImageSources;

class DataUriImageDecoder extends BinaryImageDecoder
{
    use CanDetectImageSources;

    /**
     * {@inheritdoc}
     *
     * @see DecoderInterface::supports()
     */
    public function supports(mixed $input): bool
    {
        return $this->couldBeDataUrl($input);
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
        $input = ($input instanceof DataUri) ? (string) $input : $input;

        if (!is_string($input)) {
            throw new InvalidArgumentException('Data Uri must be of type string or instance of ' . DataUri::class);
        }

        $data = DataUri::decode($input)->data();

        try {
            return parent::decode($data);
        } catch (DecoderException) {
            throw new ImageDecoderException('Data Uri contains unsupported image type');
        }
    }
}
