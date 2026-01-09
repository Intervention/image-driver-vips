<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\WidthAnalyzer as GenericWidthAnalyzer;
use Intervention\Image\Exceptions\AnalyzerException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Exception as VipsException;

class HeightAnalyzer extends GenericWidthAnalyzer implements SpecializedInterface
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
            return $vipsImage->getType('page-height') === 0 ? $vipsImage->height : $vipsImage->get('page-height');
        } catch (VipsException $e) {
            throw new AnalyzerException('Failed to retrieve image height', previous: $e);
        }
    }
}
