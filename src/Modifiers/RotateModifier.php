<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\AnimationException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\RotateModifier as GenericRotateModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;

class RotateModifier extends GenericRotateModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     * @throws VipsException|AnimationException|RuntimeException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $frames = [];
        foreach ($image as $frame) {
            $vipsImage = match ($this->rotationAngle()) {
                0.0 => $frame->native(),
                90.0, -270.0 => $frame->native()->rot90(),
                180.0, -180.0 => $frame->native()->rot180(),
                -90.0, 270.0 => $frame->native()->rot270(),
                default => $this->rotate($frame->native()),
            };

            $frames[] = $frame->setNative($vipsImage);
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }

    /**
     * @throws RuntimeException
     */
    public function rotate(VipsImage $vipsImage): VipsImage
    {
        $background = $this->driver()->handleInput($this->background);

        if (!$vipsImage->hasAlpha()) {
            $vipsImage = $vipsImage->bandjoin_const(255);
        }

        return $vipsImage->similarity([
            'background' => $background->toArray(),
            'angle' => $this->rotationAngle(),
        ]);
    }
}
