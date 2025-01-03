<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Channels\Blue;
use Intervention\Image\Colors\Rgb\Channels\Green;
use Intervention\Image\Colors\Rgb\Channels\Red;
use Intervention\Image\Drivers\Vips\Traits\PositionToGravity;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ContainModifier as GenericContainModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Extend;

class ContainModifier extends GenericContainModifier implements SpecializedInterface
{
    use PositionToGravity;

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException|VipsException
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $resize = $this->getResizeSize($image);
        $bgColor = $this->driver()->handleInput($this->background);

        $resized = $image->core()->native()->thumbnail_image($resize->width(), [
            'height' => $resize->height(),
            'no_rotate' => true,
        ]);

        if (!$resized->hasAlpha()) {
            $resized = $resized->bandjoin_const(255);
        }

        $image->core()->setNative(
            $resized->gravity(
                $this->positionToGravity($this->position),
                $resize->width(),
                $resize->height(),
                [
                    'extend' => Extend::BACKGROUND,
                    'background' => [
                        $bgColor->channel(Red::class)->value(),
                        $bgColor->channel(Green::class)->value(),
                        $bgColor->channel(Blue::class)->value(),
                        $bgColor->channel(Alpha::class)->value(),
                    ],
                ]
            )
        );

        return $image;
    }
}
