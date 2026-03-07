<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\PixelColorAnalyzer as GenericPixelColorAnalyzer;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\AnalyzerException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\CoreInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Exception as VipsException;

class PixelColorAnalyzer extends GenericPixelColorAnalyzer implements SpecializedInterface
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
        return $this->colorAt(
            $image,
            $image->core(),
            $this->x,
            $this->y,
        );
    }

    /**
     * Detects color at given position and returns it as ColorInterface
     *
     * @throws StateException
     * @throws AnalyzerException
     */
    protected function colorAt(ImageInterface $image, CoreInterface $core, int $x, int $y): ColorInterface
    {
        $core = Core::ensureInMemory($core);

        try {
            return $this->driver()
                ->colorProcessor($image)
                ->import(array_map(
                    fn(int|float $value): int => (int) max(min($value, 255), 0),
                    $core->native()->getpoint($x, $y)
                ));
        } catch (VipsException $e) {
            throw new AnalyzerException('Failed to read pixel color at position ' . $x . ', ' . $y, previous: $e);
        }
    }
}
