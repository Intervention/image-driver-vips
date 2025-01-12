<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\PixelColorAnalyzer as GenericPixelColorAnalyzer;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Image as VipsImage;

class PixelColorAnalyzer extends GenericPixelColorAnalyzer implements SpecializedInterface
{
    /**
     * @throws ColorException|RuntimeException
     */
    public function analyze(ImageInterface $image): mixed
    {
        return $this->colorAt(
            $image->colorspace(),
            $image->core()->native(),
            $this->x,
            $this->y,
        );
    }

    /**
     * @throws ColorException
     */
    protected function colorAt(ColorspaceInterface $colorspace, VipsImage $vipsImage, int $x, int $y): ColorInterface
    {
        return $this->driver()
            ->colorProcessor($colorspace)
            ->nativeToColor(array_map(
                fn(int|float $value): int => (int) max(min($value, 255), 0),
                $vipsImage->getpoint($x, $y)
            ));
    }
}
