<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Analyzers;

use Intervention\Image\Analyzers\ProfileAnalyzer as GenericProfileAnalyzer;
use Intervention\Image\Colors\Profile;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Exception as VipsException;

class ProfileAnalyzer extends GenericProfileAnalyzer implements SpecializedInterface
{
    public function analyze(ImageInterface $image): mixed
    {
        try {
            $profiles = $image->core()->native()->get('icc-profile-data');
        } catch (VipsException) {
            throw new ColorException('No ICC profile found in image.');
        }

        return new Profile($profiles);
    }
}
