<?php

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Drivers\AbstractFontProcessor;
use Intervention\Image\Geometry\Rectangle;
use Intervention\Image\Interfaces\FontInterface;
use Intervention\Image\Interfaces\SizeInterface;

class FontProcessor extends AbstractFontProcessor
{
    /**
     * {@inheritdoc}
     *
     * @see FontProcessorInterface::boxSize()
     */
    public function boxSize(string $text, FontInterface $font): SizeInterface
    {
        // TODO: Implement boxSize() method.
        return new Rectangle(0, 0);
    }
}
