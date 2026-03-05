<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\ProfileAnalyzer as GenericProfileAnalyzer;
use Intervention\Image\Colors\Profile;
use Intervention\Image\Exceptions\AnalyzerException;
use Intervention\Image\Exceptions\StreamException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Exception as VipsException;

class ProfileAnalyzer extends GenericProfileAnalyzer implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\AnalyzerInterface::analyze()
     *
     * @throws InvalidArgumentException
     * @throws AnalyzerException
     * @throws StreamException
     */
    public function analyze(ImageInterface $image): mixed
    {
        try {
            $profiles = $image->core()->native()->get('icc-profile-data');
        } catch (VipsException) {
            throw new AnalyzerException('No ICC profile found in image');
        }

        return new Profile($profiles);
    }
}
