<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Channels\Blue;
use Intervention\Image\Colors\Rgb\Channels\Green;
use Intervention\Image\Colors\Rgb\Channels\Red;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\CropModifier as GenericCropModifier;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interesting;
use Jcupitt\Vips\Interpretation;

class CropModifier extends GenericCropModifier implements SpecializedInterface
{
    public const INTERESTING_PREFIX = 'interesting-';

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException|\Jcupitt\Vips\Exception
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $originalSize = $image->size();
        $crop = $this->crop($image);
        $background = $this->background($crop);

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
            $offset_x = $crop->pivot()->x() + $this->offset_x;
            $offset_y = $crop->pivot()->y() + $this->offset_y;

            $targetWidth = min($crop->width(), $originalSize->width());
            $targetHeight = min($crop->height(), $originalSize->height());

            $targetWidth = $targetWidth > $originalSize->width() ? $targetWidth + $offset_x : $targetWidth;
            $targetHeight = $targetHeight > $originalSize->height() ? $targetHeight + $offset_y : $targetHeight;

            $cropped = $image->core()->native()->crop(
                max($offset_x, 0),
                max($offset_y, 0),
                $targetWidth,
                $targetHeight
            );

            if ($crop->width() > $originalSize->width() || $cropped->height < $crop->height()) {
                $cropped = $background->insert($cropped, $offset_x * -1, $offset_y * -1);
            }

            $image->core()->setNative($cropped);
        }

        return $image;
    }

    /**
     * @throws RuntimeException|\Jcupitt\Vips\Exception
     */
    private function background(SizeInterface $resizeTo): VipsImage
    {
        $bgColor = $this->driver()->handleInput($this->background);

        return VipsImage::black(1, 1)
            ->add($bgColor->channel(Red::class)->value())
            ->cast(BandFormat::UCHAR)
            ->embed(0, 0, $resizeTo->width(), $resizeTo->height(), ['extend' => Extend::COPY])
            ->copy(['interpretation' => Interpretation::SRGB])
            ->bandjoin([
                $bgColor->channel(Green::class)->value(),
                $bgColor->channel(Blue::class)->value(),
                $bgColor->channel(Alpha::class)->value(),
            ]);
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
}
