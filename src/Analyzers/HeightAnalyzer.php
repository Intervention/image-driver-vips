<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\WidthAnalyzer as GenericWidthAnalyzer;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class HeightAnalyzer extends GenericWidthAnalyzer implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\AnalyzerInterface::analyze()
     */
    public function analyze(ImageInterface $image): mixed
    {
        $vipsImage = $image->core()->native();

        return $vipsImage->getType('page-height') === 0 ? $vipsImage->height : $vipsImage->get('page-height');
    }
}
