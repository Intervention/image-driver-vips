<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\PixelColorAnalyzer as GenericPixelColorAnalyzer;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Image as VipsImage;

class PixelColorAnalyzer extends GenericPixelColorAnalyzer implements SpecializedInterface
{
    public function analyze(ImageInterface $image): mixed
    {
        return $this->colorAt(
            $image->colorspace(),
            $image->core()->native()
        );
    }

    /**
     * @throws ColorException
     */
    protected function colorAt(ColorspaceInterface $colorspace, VipsImage $vipsImage): ColorInterface
    {
        $normalizer = function ($value) use ($vipsImage): int {
            if ($vipsImage->format === BandFormat::UCHAR) {
                return (int) $value;
            }

            // Normalize for other formats (e.g., 16-bit images or float)
            $maxValue = 255; // Max for 8-bit images
            $minValue = $vipsImage->min();
            $range = $vipsImage->max() - $minValue;

            return (int) round((($value - $minValue) / $range) * $maxValue);
        };

        return $this->driver()
            ->colorProcessor($colorspace)
            ->nativeToColor(array_map(
                $normalizer,
                $vipsImage->getpoint($this->x, $this->y)
            ));
    }
}
