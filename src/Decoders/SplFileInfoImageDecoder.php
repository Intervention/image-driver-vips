<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Decoders;

use SplFileInfo;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ImageInterface;

class SplFileInfoImageDecoder extends FilePathImageDecoder
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\DecoderInterface::decode()
     */
    public function decode(mixed $input): ImageInterface|ColorInterface
    {
        if (!is_a($input, SplFileInfo::class)) {
            throw new DecoderException('Unable to decode input');
        }

        return parent::decode($input->getRealPath());
    }
}
