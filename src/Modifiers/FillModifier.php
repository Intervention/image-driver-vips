<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\FillModifier as GenericFillModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Exception as VipsException;

class FillModifier extends GenericFillModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws StateException
     * @throws ModifierException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $color = $this->driver()->colorProcessor($image)->export(
            $this->color()
        );

        try {
            $overlay = VipsImage::black(1, 1)
                ->add(array_slice($color, 0, 1))
                ->cast($image->core()->native()->format)
                ->embed(
                    0,
                    0,
                    $image->core()->native()->width,
                    $image->core()->native()->height,
                    ['extend' => Extend::COPY],
                )
                ->copy(['interpretation' => $image->core()->native()->interpretation])
                ->bandjoin(array_slice($color, 1));
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to fill image with color',
                previous: $e
            );
        }

        // flood fill
        if ($this->hasPosition()) {
            try {
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
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to fill image with color',
                    previous: $e
                );
            }
        }

        try {
            $native = $image->core()->native()->composite2($overlay, BlendMode::OVER);
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to fill image with color',
                previous: $e
            );
        }

        $image->core()->setNative($native);

        return $image;
    }
}
