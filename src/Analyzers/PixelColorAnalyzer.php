<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\PixelColorAnalyzer as GenericPixelColorAnalyzer;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\CoreInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class PixelColorAnalyzer extends GenericPixelColorAnalyzer implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws ColorException|RuntimeException
     * @see Intervention\Image\Interfaces\AnalyzerInterface::analyze()
     */
    public function analyze(ImageInterface $image): mixed
    {
        return $this->colorAt(
            $image->colorspace(),
            $image->core(),
            $this->x,
            $this->y,
        );
    }

    /**
     * Detects color at given position and returns it as ColorInterface
     *
     * @throws ColorException
     */
    protected function colorAt(ColorspaceInterface $colorspace, CoreInterface $core, int $x, int $y): ColorInterface
    {
        $core = Core::ensureInMemory($core);

        return $this->driver()
            ->colorProcessor($colorspace)
            ->nativeToColor(array_map(
                fn(int|float $value): int => (int) max(min($value, 255), 0),
                $core->native()->getpoint($x, $y)
            ));
    }
}
