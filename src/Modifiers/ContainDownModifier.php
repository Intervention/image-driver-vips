<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Jcupitt\Vips\Extend;

class ContainDownModifier extends ContainModifier
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws InvalidArgumentException
     * @throws StateException
     * @throws DriverException
     * @throws ModifierException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $targetSize = $this->resizeSize($image);
        $colorspace = $image->colorspace();
        $bgColor = $this->driver()->colorProcessor($image)->export(
            $this->backgroundColor()
        );

        if (!$image->isAnimated()) {
            $contained = $this->containDown(
                $image->core()->first(),
                $targetSize,
                $bgColor,
                $colorspace,
            )->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->containDown(
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
     * Apply image resizing to given frame.
     *
     * @param array<float> $background
     * @throws ModifierException
     */
    private function containDown(
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

        try {
            $resized = $resized->gravity(
                $this->alignmentToGravity($this->alignment),
                $targetSize->width(),
                $targetSize->height(),
                [
                    'extend' => Extend::BACKGROUND,
                    'background' => $background,
                ]
            );
        } catch (InvalidArgumentException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to process resizing',
                previous: $e
            );
        }

        $frame->setNative($resized);

        return $frame;
    }
}
