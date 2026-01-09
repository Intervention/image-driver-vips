<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\ColorspaceAnalyzer as GenericColorspaceAnalyzer;
use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Exceptions\AnalyzerException;
use Intervention\Image\Exceptions\ColorDecoderException;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class ColorspaceAnalyzer extends GenericColorspaceAnalyzer implements SpecializedInterface
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
        try {
            return ColorProcessor::interpretationToColorspace($image->core()->native()->interpretation);
        } catch (ColorDecoderException) {
            throw new AnalyzerException(
                "Failed to resolve driver's colorspace interpretation to instance of " . ColorspaceInterface::class,
            );
        }
    }
}
