<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Driver;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\DrawBezierModifier as GenericDrawBezierModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;

class DrawBezierModifier extends GenericDrawBezierModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws VipsException|RuntimeException|\RuntimeException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $chunks = array_chunk($this->drawable->toArray(), 2);
        $points = implode(' ', array_map(function (array $coordinates, int $key) use ($chunks): string {
            return match ($key) {
                0 => 'M' . implode(' ', $coordinates),
                1 => count($chunks) === 3 ? 'Q' . implode(' ', $coordinates) : 'C' . implode(' ', $coordinates),
                default => implode(' ', $coordinates),
            };
        }, $chunks, array_keys($chunks)));

        $bezier = Driver::createShape(
            'path',
            [
                'd' => $points,
                'fill' => $this->backgroundColor()->toString(),
                'stroke' => $this->borderColor()->toString(),
                'stroke-width' => $this->drawable->borderSize(),
            ],
            $image->width(),
            $image->height(),
        );

        $frames = [];
        foreach ($image as $frame) {
            $frames[] = $frame->setNative(
                $frame->native()->composite($bezier, [BlendMode::OVER])
            );
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }
}
