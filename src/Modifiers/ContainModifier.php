<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Alignment;
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
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ContainModifier as GenericContainModifier;
use Jcupitt\Vips\CompassDirection;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Extend;

class ContainModifier extends GenericContainModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws StateException
     * @throws ModifierException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        try {
            $targetSize = $this->resizeSize($image);
        } catch (InvalidArgumentException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to calculate target size',
                previous: $e
            );
        }

        $colorspace = $image->colorspace();
        $bgColor = $this->driver()->colorProcessor($image)->export(
            $this->backgroundColor()
        );

        if (!$image->isAnimated()) {
            $contained = $this->contain(
                $image->core()->first(),
                $targetSize,
                $bgColor,
                $colorspace,
            )->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->contain(
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
     * @param array<int> $bgColor
     * @throws ModifierException
     */
    private function contain(
        FrameInterface $frame,
        SizeInterface $resize,
        array $bgColor,
        ColorspaceInterface $colorspace,
    ): FrameInterface {
        try {
            $resized = $frame->native()->thumbnail_image($resize->width(), [
                'height' => $resize->height(),
                'no_rotate' => true,
                'export-profile' => ColorProcessor::colorspaceToInterpretation($colorspace),
            ]);

            try {
                $native = $resized->gravity(
                    $this->alignmentToGravity($this->alignment),
                    $resize->width(),
                    $resize->height(),
                    [
                        'extend' => Extend::BACKGROUND,
                        'background' => $bgColor,
                    ]
                );
            } catch (InvalidArgumentException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to convert alignment value',
                    previous: $e
                );
            }
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to process resizing',
                previous: $e
            );
        }

        $frame->setNative($native);

        return $frame;
    }

    /**
     * Convert alignment to libvips gravity.
     *
     * @throws InvalidArgumentException
     */
    protected function alignmentToGravity(string|Alignment $alignment): string
    {
        $alignment = Alignment::create($alignment); // normalize alignment

        return match ($alignment) {
            Alignment::TOP => CompassDirection::NORTH,
            Alignment::TOP_RIGHT => CompassDirection::NORTH_EAST,
            Alignment::LEFT => CompassDirection::WEST,
            Alignment::RIGHT => CompassDirection::EAST,
            Alignment::BOTTOM_LEFT => CompassDirection::SOUTH_WEST,
            Alignment::BOTTOM => CompassDirection::SOUTH,
            Alignment::BOTTOM_RIGHT => CompassDirection::SOUTH_EAST,
            Alignment::TOP_LEFT => CompassDirection::NORTH_WEST,
            default => CompassDirection::CENTRE,
        };
    }
}
