<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Collection;
use Intervention\Image\Exceptions\AnalyzerException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class PixelColorsAnalyzer extends PixelColorAnalyzer implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\AnalyzerInterface::analyze()
     *
     * @throws StateException
     * @throws AnalyzerException
     */
    public function analyze(ImageInterface $image): mixed
    {
        $colors = new Collection();
        $height = $image->height();

        for ($i = 0; $i < $image->count(); $i++) {
            $colors->push(
                $this->colorAt(
                    $image,
                    $image->core(),
                    $this->x,
                    $this->y + $i * $height
                )
            );
        }

        return $colors;
    }
}
