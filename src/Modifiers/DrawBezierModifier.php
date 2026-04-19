<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Colors\Rgb\Colorspace as Rgb;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\DrawBezierModifier as GenericDrawBezierModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Exception as VipsException;

class DrawBezierModifier extends GenericDrawBezierModifier implements SpecializedInterface
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
        $bezier = $this->bezier($image);
        $frames = [];

        foreach ($image as $frame) {
            try {
                $native = $frame->native()->composite($bezier, [BlendMode::OVER]);
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to draw bezier curve',
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
     * Build bezier curve image with size of the original image.
     *
     * @throws StateException
     * @throws DriverException
     */
    private function bezier(ImageInterface $image): VipsImage
    {
        // build bezier points
        $chunks = array_chunk($this->drawable->toArray(), 2);
        $points = implode(' ', array_map(function (array $coordinates, int $key) use ($chunks): string {
            return match ($key) {
                0 => 'M' . implode(' ', $coordinates),
                1 => count($chunks) === 3 ? 'Q' . implode(' ', $coordinates) : 'C' . implode(' ', $coordinates),
                default => implode(' ', $coordinates),
            };
        }, $chunks, array_keys($chunks)));

        // setup shape attributes
        $shapeAttributes = [
            'fill' => 'rgba(0, 0, 0, 0)',
            'd' => $points,
        ];

        if ($this->drawable->hasBackgroundColor()) {
            $shapeAttributes['fill'] = $this->backgroundColor()->toColorspace(Rgb::class)->toString();
        }

        if ($this->drawable->hasBorder()) {
            $shapeAttributes['stroke'] = $this->borderColor()->toColorspace(Rgb::class)->toString();
            $shapeAttributes['stroke-width'] = $this->drawable()->borderSize();
        }

        // create final shape
        return Driver::createShape(
            'path',
            $shapeAttributes,
            $image->width(),
            $image->height(),
        );
    }
}
