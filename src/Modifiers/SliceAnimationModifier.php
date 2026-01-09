<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\SliceAnimationModifier as GenericSliceAnimationModifier;

class SliceAnimationModifier extends GenericSliceAnimationModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws InvalidArgumentException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        if ($this->offset >= $image->count()) {
            throw new InvalidArgumentException('Offset #' . $this->offset . ' is not in the range of frames');
        }

        $image->core()->slice($this->offset, $this->length);

        return $image;
    }
}
