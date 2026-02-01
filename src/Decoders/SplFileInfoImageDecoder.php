<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Exceptions\DirectoryNotFoundException;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\FileNotFoundException;
use SplFileInfo;
use Intervention\Image\Exceptions\FileNotReadableException;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Traits\CanParseFilePath;

class SplFileInfoImageDecoder extends FilePathImageDecoder
{
    use CanParseFilePath;

    /**
     * {@inheritdoc}
     *
     * @see DecoderInterface::supports()
     */
    public function supports(mixed $input): bool
    {
        return $input instanceof SplFileInfo;
    }

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     *
     * @throws InvalidArgumentException
     * @throws DirectoryNotFoundException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws ImageDecoderException
     * @throws DriverException
     * @throws StateException
     */
    public function decode(mixed $input): ImageInterface
    {
        if (!$input instanceof SplFileInfo) {
            throw new InvalidArgumentException('Image source must be of type ' . SplFileInfo::class);
        }

        try {
            return parent::decode($this->filePathFromSplFileInfoOrFail($input));
        } catch (DecoderException) {
            throw new ImageDecoderException(SplFileInfo::class . ' contains unsupported image type');
        }
    }
}
