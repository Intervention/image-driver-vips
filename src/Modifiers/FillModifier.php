<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Channels\Blue;
use Intervention\Image\Colors\Rgb\Channels\Green;
use Intervention\Image\Colors\Rgb\Channels\Red;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\FillModifier as GenericFillModifier;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

class FillModifier extends GenericFillModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException|\Jcupitt\Vips\Exception
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $color = $this->color();

        $overlay = VipsImage::black(1, 1)
            ->add($color->channel(Red::class)->value())
            ->cast(BandFormat::UCHAR)
            ->embed(0, 0, $image->core()->native()->width, $image->core()->native()->height, ['extend' => Extend::COPY])
            ->copy(['interpretation' => Interpretation::SRGB])
            ->bandjoin([
                $color->channel(Green::class)->value(),
                $color->channel(Blue::class)->value(),
                $color->channel(Alpha::class)->value(),
            ]);

        if ($this->hasPosition()) {
            $mask = VipsImage::black($image->core()->native()->width, $image->core()->native()->height);
            $mask = $mask->draw_flood(
                [255],
                $this->position->x(),
                $this->position->y(),
                [
                    'equal' => true,
                    'test' => $image->core()->native(),
                ]
            );

            if ($overlay->hasAlpha()) {
                $mask = $mask->composite2(
                    $overlay->extract_band($overlay->bands - 1, ['n' => 1]),
                    BlendMode::DARKEN
                );
                $overlay = $overlay->extract_band(0, ['n' => $overlay->bands - 1]);
            }

            $overlay = $overlay->bandjoin($mask[0]);
        }

        $image->core()->setNative(
            $image->core()->native()->composite2($overlay, BlendMode::OVER)
        );

        return $image;
    }

    /**
     * @throws RuntimeException
     */
    private function color(): ColorInterface
    {
        return $this->driver()->handleInput($this->color);
    }
}
