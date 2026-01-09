<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ResizeModifier as GenericResizeModifier;

class ResizeModifier extends GenericResizeModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $resizeTo = $this->adjustedSize($image);

        $image->core()->setNative(
            $image->core()->native()->thumbnail_image($resizeTo->width(), [
                'height' => $resizeTo->height(),
                'size' => 'force',
                'no_rotate' => true,
                'export-profile' => ColorProcessor::colorspaceToInterpretation($image->colorspace()),
            ])
        );

        return $image;
    }

    /**
     * Return the size the modifier will resize to
     */
    protected function adjustedSize(ImageInterface $image): SizeInterface
    {
        return $image->size()->resize($this->width, $this->height);
    }
}
