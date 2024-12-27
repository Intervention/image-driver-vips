<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\GeometryException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ResizeModifier as GenericResizeModifier;

class ResizeModifier extends GenericResizeModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $resizeTo = $this->getAdjustedSize($image);

        $image->core()->setNative(
            $image->core()->native()->thumbnail_image($resizeTo->width(), [
                'height' => $resizeTo->height(),
                'size' => 'force',
                'no_rotate' => true,
            ])
        );

        return $image;
    }

    /**
     * Return the size the modifier will resize to
     *
     * @param ImageInterface $image
     * @throws RuntimeException
     * @throws GeometryException
     * @return SizeInterface
     */
    protected function getAdjustedSize(ImageInterface $image): SizeInterface
    {
        return $image->size()->resize($this->width, $this->height);
    }
}
