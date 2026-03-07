<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
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
     *
     * @throws ModifierException
     * @throws StateException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $backgroundColor = $this->driver()
            ->colorProcessor($image)
            ->export($this->backgroundColor());

        $frames = [];
        foreach ($image as $frame) {
            try {
                $native = match ($this->rotationAngle()) {
                    0.0 => $frame->native(),
                    90.0, -270.0 => $frame->native()->rot90(),
                    180.0, -180.0 => $frame->native()->rot180(),
                    -90.0, 270.0 => $frame->native()->rot270(),
                    default => $this->rotate($frame->native(), $backgroundColor),
                };
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to rotate image',
                    previous: $e
                );
            }

            $frames[] = $frame->setNative($native);
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }

    /**
     * @param array<float> $backgroundColor
     */
    private function rotate(VipsImage $vipsImage, array $backgroundColor): VipsImage
    {
        if (!$vipsImage->hasAlpha()) {
            $vipsImage = $vipsImage->bandjoin_const(255);
        }

        return $vipsImage->similarity([
            'angle' => $this->rotationAngle(), // TODO: check rotation direction
            'background' => $backgroundColor,
        ]);
    }
}
