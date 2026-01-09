<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\CoverModifier as GenericCoverModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;

class CoverModifier extends GenericCoverModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws ModifierException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        try {
            $crop = $this->cropSize($image);
            $resize = $this->resizeSize($crop);
        } catch (InvalidArgumentException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to calculate target size',
                previous: $e
            );
        }

        $frames = [];
        foreach ($image as $frame) {
            $native = $this->cropResizeFrame($frame, $crop, $resize, $image->colorspace());
            $frames[] = $frame->setNative($native);
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }

    /**
     * @throws ModifierException
     */
    private function cropResizeFrame(
        FrameInterface $frame,
        SizeInterface $cropSize,
        SizeInterface $resizeSize,
        ColorspaceInterface $colorspace
    ): VipsImage {
        try {
            return $frame->native()->crop(
                $cropSize->pivot()->x(),
                $cropSize->pivot()->y(),
                $cropSize->width(),
                $cropSize->height()
            )->thumbnail_image($resizeSize->width(), [
                'height' => $resizeSize->height(),
                'size' => 'force',
                'no_rotate' => true,
                'export-profile' => ColorProcessor::colorspaceToInterpretation($colorspace),
            ]);
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to process resizing',
                previous: $e
            );
        }
    }
}
