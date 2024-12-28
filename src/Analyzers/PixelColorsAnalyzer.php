<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Collection;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class PixelColorsAnalyzer extends PixelColorAnalyzer implements SpecializedInterface
{
    /**
     * @throws ColorException|RuntimeException
     */
    public function analyze(ImageInterface $image): mixed
    {
        $colors = new Collection();
        $height = $image->height();

        for ($i = 0; $i < $image->count(); $i++) {
            $colors->push(
                $this->colorAt(
                    $image->colorspace(),
                    $image->core()->native(),
                    $this->x,
                    $this->y + $i * $height
                )
            );
        }

        return $colors;
    }
}
