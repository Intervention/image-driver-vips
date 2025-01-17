<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use Exception;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Jcupitt\Vips;

class FilePathImageDecoder extends NativeObjectDecoder
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     */
    public function decode(mixed $input): ImageInterface|ColorInterface
    {
        if (!$this->isFile($input)) {
            throw new DecoderException('Unable to decode input');
        }

        try {
            $vipsImage = Vips\Image::newFromFile($input . '[' . $this->stringOptions() . ']', [
                'access' => Vips\Access::SEQUENTIAL,
            ]);
        } catch (Exception) {
            throw new DecoderException('Unable to decode input');
        }

        $image = parent::decode($vipsImage);

        // set file path on origin
        $image->origin()->setFilePath($input);

        // extract exif data for the appropriate formats
        if (in_array($this->vipsMediaType($vipsImage)?->format(), [Format::JPEG, Format::TIFF])) {
            $image->setExif($this->extractExifData($input));
        }

        return $image;
    }
}
