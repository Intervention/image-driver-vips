<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Channels\Alpha;
use Intervention\Image\Colors\Rgb\Channels\Blue;
use Intervention\Image\Colors\Rgb\Channels\Green;
use Intervention\Image\Colors\Rgb\Channels\Red;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\RotateModifier as GenericRotateModifier;
use Jcupitt\Vips\Image as VipsImage;

class RotateModifier extends GenericRotateModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $core = $image->core()->native();

        $core = match ($this->rotationAngle()) {
            0.0 => $core,
            90.0, -270.0 => $core->rot90(),
            180.0, -180.0 => $core->rot180(),
            -90.0, 270.0 => $core->rot270(),
            default => $this->rotate($core),
        };

        $image->core()->setNative($core);

        return $image;
    }

    /**
     * @throws RuntimeException
     */
    public function rotate(VipsImage $core): VipsImage
    {
        $color = $this->driver()->handleInput($this->background);

        $background = [
            $color->channel(Red::class)->value(),
            $color->channel(Green::class)->value(),
            $color->channel(Blue::class)->value(),
        ];

        if ($color->isTransparent() && !$core->hasAlpha()) {
            $core = $core->bandjoin_const(255);
            $background[] = $color->channel(Alpha::class)->value();
        }

        return $core->similarity([
            'background' => $background,
            'angle' => $this->rotationAngle(),
        ]);
    }
}
