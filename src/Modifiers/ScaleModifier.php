<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;

class ScaleModifier extends ResizeModifier
{
    /**
     * {@inheritdoc}
     *
     * @see ResizeModifier::getAdjustedSize()
     */
    protected function getAdjustedSize(ImageInterface $image): SizeInterface
    {
        return $image->size()->scale($this->width, $this->height);
    }
}
