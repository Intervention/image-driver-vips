<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;

class ResizeDownModifier extends ResizeModifier
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Drivers\Modifiers\ResizeModifier::getAdjustedSize()
     */
    protected function getAdjustedSize(ImageInterface $image): SizeInterface
    {
        return $image->size()->resizeDown($this->width, $this->height);
    }
}
