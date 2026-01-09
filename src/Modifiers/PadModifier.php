<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Jcupitt\Vips\Extend;

class PadModifier extends ContainModifier
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws InvalidArgumentException
     * @throws StateException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $targetSize = $this->resizeSize($image);
        $colorspace = $image->colorspace();
        $bgColor = $this->driver()->colorProcessor($image)->colorToNative(
            $this->backgroundColor()
        );

        if (!$image->isAnimated()) {
            $contained = $this->pad(
                $image->core()->first(),
                $targetSize,
                $bgColor,
                $colorspace,
            )->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->pad(
                    $frame,
                    $targetSize,
                    $bgColor,
                    $colorspace,
                );
            }

            $contained = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($contained);

        return $image;
    }

    /**
     * Apply padded image resizing
     *
     * @param array<float> $background
     */
    private function pad(
        FrameInterface $frame,
        SizeInterface $targetSize,
        array $background,
        ColorspaceInterface $colorspace,
    ): FrameInterface {
        $cropWidth = min($frame->native()->width, $targetSize->width());
        $cropHeight = min($frame->native()->height, $targetSize->height());

        $resized = $frame->native()->thumbnail_image($cropWidth, [
            'height' => $cropHeight,
            'no_rotate' => true,
            'export-profile' => ColorProcessor::colorspaceToInterpretation($colorspace),
        ]);

        $resized = $resized->gravity(
            $this->alignmentToGravity($this->alignment),
            $targetSize->width(),
            $targetSize->height(),
            [
                'extend' => Extend::BACKGROUND,
                'background' => $background,
            ]
        );

        $frame->setNative($resized);

        return $frame;
    }
}
