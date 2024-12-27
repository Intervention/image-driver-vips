<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\TrimModifier as GenericTrimModifier;
use Jcupitt\Vips\BandFormat;

class TrimModifier extends GenericTrimModifier implements SpecializedInterface
{
    public function apply(ImageInterface $image): ImageInterface
    {
        if ($image->isAnimated()) {
            throw new NotSupportedException('Trim modifier cannot be applied to animated images.');
        }

        $core = $image->core()->native();

        if ($image->core()->native()->hasAlpha()) {
            // extract alpha channel
            $core = $image->core()->native()->extract_band($image->core()->native()->bands - 1, ['n' => 1]);
        }

        // get the color of the 4 corners
        $points = [
            $core->getpoint(0, 0),
            $core->getpoint($image->width() - 1, 0),
            $core->getpoint(0, $image->height() - 1),
            $core->getpoint($image->width() - 1, $image->height() - 1),
        ];

        $maxThreshold = match ($image->core()->native()->format) {
            BandFormat::UCHAR => 255,
            BandFormat::USHORT => 65535,
            BandFormat::FLOAT => 1,
            default => 255,
        };

        foreach ($points as $point) {
            unset($point[3]); // remove alpha

            $trim = $core->find_trim([
                'threshold' => min($this->tolerance, $maxThreshold),
                'background' => $point,
            ]);

            $image->core()->setNative(
                $core->crop(
                    min($trim['top'], $image->height() - 1),
                    min($trim['left'], $image->width() - 1),
                    max($trim['width'], 1),
                    max($trim['height'], 1)
                )
            );
        }

        return $image;
    }
}
