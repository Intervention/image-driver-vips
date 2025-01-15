<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\SpecializableModifier;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\BandFormat;

class TrimModifier extends SpecializableModifier implements SpecializedInterface
{
    public function __construct(public int $tolerance = 40)
    {
        //
    }

    /**
     * {@inheritdoc}
     *
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        if ($image->isAnimated()) {
            throw new NotSupportedException('Trim modifier cannot be applied to animated images.');
        }

        $core = Core::ensureInMemory($image->core());
        $native = $core->native();

        // get the color of the 4 corners
        $points = [
            $native->getpoint(0, 0),
            $native->getpoint($image->width() - 1, 0),
            $native->getpoint(0, $image->height() - 1),
            $native->getpoint($image->width() - 1, $image->height() - 1),
        ];

        $maxThreshold = match ($image->core()->native()->format) {
            BandFormat::USHORT => 65535,
            BandFormat::FLOAT => 1,
            default => 255,
        };

        foreach ($points as $point) {
            $trim = $native->find_trim([
                'threshold' => min($this->tolerance + 10, $maxThreshold),
                'background' => $point,
            ]);

            $native = $native->crop(
                min($trim['left'], $image->width() - 1),
                min($trim['top'], $image->height() - 1),
                max($trim['width'], 1),
                max($trim['height'], 1)
            );

            if ($trim['width'] === 0 || $trim['height'] === 0) {
                break;
            }
        }

        $image->core()->setNative($native);

        return $image;
    }
}
