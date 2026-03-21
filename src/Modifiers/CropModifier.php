<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\CropModifier as GenericCropModifier;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interesting;
use Jcupitt\Vips\Exception as VipsException;

class CropModifier extends GenericCropModifier implements SpecializedInterface
{
    public const INTERESTING_PREFIX = 'interesting-';

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
            $originalSize = $image->size();
            $crop = $this->crop($image);
        } catch (InvalidArgumentException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to calculate target size',
                previous: $e
            );
        }

        $background = $this->background($crop, $image);

        if (
            in_array($this->alignment, $this->interestingPositions()) &&
            (
                $crop->width() < $originalSize->width() ||
                $crop->height() < $originalSize->height()
            )
        ) {
            try {
                $cropped = $image->core()->native()->smartcrop(
                    $crop->width(),
                    $crop->height(),
                    ['interesting' => str_replace(self::INTERESTING_PREFIX, '', $this->alignment)]
                );
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to process resizing',
                    previous: $e
                );
            }
            $image->core()->setNative($cropped);
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $frame->setNative($this->cropFrame($frame, $crop, $originalSize, $background));
            }

            $image->core()->setNative(
                Core::replaceFrames($image->core()->native(), $frames)
            );
        }

        return $image;
    }

    /**
     * {@inheritdoc}
     *
     * @see GenericCropModifier::crop()
     */
    protected function crop(ImageInterface $image): SizeInterface
    {
        if (is_string($this->alignment) && str_starts_with($this->alignment, self::INTERESTING_PREFIX)) {
            $originalAlignment = $this->alignment;
            $this->alignment = Alignment::TOP_LEFT;

            $cropped = parent::crop($image);

            $this->alignment = $originalAlignment;

            return $cropped;
        }

        return parent::crop($image);
    }

    /**
     * @throws StateException
     * @throws ModifierException
     */
    private function background(SizeInterface $resizeTo, ImageInterface $image): VipsImage
    {
        $backgroundColor = $this->driver()->colorProcessor($image)->export(
            $this->backgroundColor()
        );

        try {
            return VipsImage::black(1, 1)
                ->add(array_slice($backgroundColor, 0, 1))
                ->cast($image->core()->native()->format)
                ->embed(0, 0, $resizeTo->width(), $resizeTo->height(), ['extend' => Extend::COPY])
                ->copy(['interpretation' => $image->core()->native()->interpretation])
                ->bandjoin(array_slice($backgroundColor, 1));
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to build background color',
                previous: $e
            );
        }
    }

    private function cropFrame(
        FrameInterface $frame,
        SizeInterface $crop,
        SizeInterface $originalSize,
        VipsImage $background
    ): VipsImage {
        $offsetX = $crop->pivot()->x() + $this->x;
        $offsetY = $crop->pivot()->y() + $this->y;

        $targetWidth = min($crop->width(), $originalSize->width());
        $targetHeight = min($crop->height(), $originalSize->height());

        $targetWidth = $targetWidth > $originalSize->width() ? $targetWidth + $offsetX : $targetWidth;
        $targetHeight = $targetHeight > $originalSize->height() ? $targetHeight + $offsetY : $targetHeight;

        $cropped = $frame->native()->crop(
            max($offsetX, 0),
            max($offsetY, 0),
            $targetWidth,
            $targetHeight
        );

        if ($crop->width() > $originalSize->width() || $cropped->height < $crop->height()) {
            $cropped = $background->insert(
                $cropped,
                max($offsetX * -1, 0),
                max($offsetY * -1, 0)
            );
        }

        return $cropped;
    }

    /**
     * Smart crop interesting positions, prefixed with `interesting-`.
     *
     * @return list<string>
     */
    private function interestingPositions(): array
    {
        return array_map(fn(string $position): string => self::INTERESTING_PREFIX . $position, [
            Interesting::NONE,
            Interesting::CENTRE,
            Interesting::ENTROPY,
            Interesting::ATTENTION,
            Interesting::LOW,
            Interesting::HIGH,
            Interesting::ALL,
        ]);
    }
}
