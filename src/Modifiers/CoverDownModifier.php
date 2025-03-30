<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\GeometryException;
use Intervention\Image\Interfaces\SizeInterface;

class CoverDownModifier extends CoverModifier
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws GeometryException
     */
    public function getResizeSize(SizeInterface $size): SizeInterface
    {
        return $size->resizeDown($this->width, $this->height);
    }
}
