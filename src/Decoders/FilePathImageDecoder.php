<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Intervention\Image\Exceptions\DirectoryNotFoundException;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\FileNotFoundException;
use Intervention\Image\Exceptions\FileNotReadableException;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Traits\CanDetectImageSources;
use Intervention\Image\Traits\CanParseFilePath;
use Jcupitt\Vips;
use Jcupitt\Vips\Exception as VipsException;

class FilePathImageDecoder extends NativeObjectDecoder
{
    use CanDetectImageSources;
    use CanParseFilePath;

    /**
     * {@inheritdoc}
     *
     * @see DecoderInterface::supports()
     */
    public function supports(mixed $input): bool
    {
        return $this->couldBeFilePath($input);
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
     * @throws DriverException
     * @throws StateException
     * @throws ImageDecoderException
     */
    public function decode(mixed $input): ImageInterface
    {
        $path = $this->readableFilePathOrFail($input);

        try {
            $vipsImage = Vips\Image::newFromFile($path . '[' . $this->stringOptions() . ']', [
                'access' => Vips\Access::SEQUENTIAL,
            ]);
        } catch (VipsException) {
            throw new ImageDecoderException(
                'File contains unsupported image format'
            );
        }

        $image = parent::decode($vipsImage);

        // set file path on origin
        $image->origin()->setFilePath($path);

        // extract exif data for the appropriate formats
        if (in_array($this->vipsMediaType($vipsImage)?->format(), [Format::JPEG, Format::TIFF])) {
            $image->setExif($this->extractExifData($path));
        }

        return $image;
    }
}
