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
     * @see ModifierInterface::apply()
     * @throws VipsException|RuntimeException|\RuntimeException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $chunks = array_chunk($this->drawable->toArray(), 2);
        $points = join(' ', array_map(function (array $coordinates, int $key) use ($chunks): string {
            return match ($key) {
                0 => 'M' . join(' ', $coordinates),
                1 => count($chunks) === 3 ? 'Q' . join(' ', $coordinates) : 'C' . join(' ', $coordinates),
                default => join(' ', $coordinates),
            };
        }, $chunks, array_keys($chunks)));

        $polygon = Driver::createShape(
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
                $frame->native()->composite($polygon->core()->native(), [BlendMode::OVER])
            );
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames)
        );

        return $image;
    }
}
