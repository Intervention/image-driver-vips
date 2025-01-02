<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ProfileModifier as GenericProfileModifier;

class ProfileModifier extends GenericProfileModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // create temporary file for profile because `icc_transform` only supports file paths
        $tempFile = tempnam(sys_get_temp_dir(), 'php_');
        file_put_contents($tempFile, (string) $this->profile);

        // transform to profile
        $vipsImage = $image->core()->native()->icc_transform($tempFile);

        // set transformed image
        $image->core()->setNative($vipsImage);

        // remove temporary file
        unlink($tempFile);

        return $image;
    }
}
