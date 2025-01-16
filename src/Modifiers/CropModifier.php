<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Channels\Blue;
use Intervention\Image\Colors\Rgb\Channels\Green;
use Intervention\Image\Colors\Rgb\Channels\Red;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\CropModifier as GenericCropModifier;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interesting;

class CropModifier extends GenericCropModifier implements SpecializedInterface
{
    public const INTERESTING_PREFIX = 'interesting-';

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException|\Jcupitt\Vips\Exception
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $originalSize = $image->size();
        $crop = $this->crop($image);
        $background = $this->background($crop, $image);

        if (
            in_array($this->position, $this->getInterestingPositions()) &&
            (
                $crop->width() < $originalSize->width() ||
                $crop->height() < $originalSize->height()
            )
        ) {
            $image->core()->setNative(
                $image->core()->native()->smartcrop(
                    $crop->width(),
                    $crop->height(),
                    ['interesting' => str_replace(self::INTERESTING_PREFIX, '', $this->position)]
                )
            );
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
     * @throws RuntimeException|\Jcupitt\Vips\Exception
     */
    private function background(SizeInterface $resizeTo, ImageInterface $image): VipsImage
    {
        $bgColor = $this->driver()->handleInput($this->background);

        $bands = [
            $bgColor->channel(Green::class)->value(),
            $bgColor->channel(Blue::class)->value(),
        ];

        // original image and background must have the same number of bands
        if ($image->core()->native()->hasAlpha()) {
            $bands[] = $bgColor->channel(Alpha::class)->value();
        }

        return VipsImage::black(1, 1)
            ->add($bgColor->channel(Red::class)->value())
            ->cast($image->core()->native()->format)
            ->embed(0, 0, $resizeTo->width(), $resizeTo->height(), ['extend' => Extend::COPY])
            ->copy(['interpretation' => $image->core()->native()->interpretation])
            ->bandjoin($bands);
    }

    /**
     * Smart crop interesting positions, prefixed with `interesting-`.
     *
     * @return list<string>
     */
    private function getInterestingPositions(): array
    {
        return array_map(fn (string $position): string => self::INTERESTING_PREFIX . $position, [
            Interesting::NONE,
            Interesting::CENTRE,
            Interesting::ENTROPY,
            Interesting::ATTENTION,
            Interesting::LOW,
            Interesting::HIGH,
            Interesting::ALL,
        ]);
    }

    private function cropFrame(
        FrameInterface $frame,
        SizeInterface $crop,
        SizeInterface $originalSize,
        VipsImage $background
    ): VipsImage {
        $offset_x = $crop->pivot()->x() + $this->offset_x;
        $offset_y = $crop->pivot()->y() + $this->offset_y;

        $targetWidth = min($crop->width(), $originalSize->width());
        $targetHeight = min($crop->height(), $originalSize->height());

        $targetWidth = $targetWidth > $originalSize->width() ? $targetWidth + $offset_x : $targetWidth;
        $targetHeight = $targetHeight > $originalSize->height() ? $targetHeight + $offset_y : $targetHeight;

        $cropped = $frame->native()->crop(
            max($offset_x, 0),
            max($offset_y, 0),
            $targetWidth,
            $targetHeight
        );

        if ($crop->width() > $originalSize->width() || $cropped->height < $crop->height()) {
            $cropped = $background->insert(
                $cropped,
                max($offset_x * -1, 0),
                max($offset_y * -1, 0)
            );
        }

        return $cropped;
    }
}
