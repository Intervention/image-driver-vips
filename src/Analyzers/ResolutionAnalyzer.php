<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\ResolutionAnalyzer as GenericResolutionAnalyzer;
use Intervention\Image\Exceptions\AnalyzerException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Resolution;

class ResolutionAnalyzer extends GenericResolutionAnalyzer implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\AnalyzerInterface::analyze()
     *
     * @throws AnalyzerException
     */
    public function analyze(ImageInterface $image): mixed
    {
        $vipsImage = $image->core()->native();

        try {
            return new Resolution(
                round($vipsImage->xres * 25.4),
                round($vipsImage->yres * 25.4)
            );
        } catch (InvalidArgumentException $e) {
            throw new AnalyzerException('Invalid image resolution', previous: $e);
        }
    }
}
