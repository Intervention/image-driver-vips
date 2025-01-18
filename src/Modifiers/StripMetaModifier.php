<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Collection;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;
use Intervention\Image\Interfaces\SpecializedInterface;

class StripMetaModifier implements ModifierInterface, SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $vipImage = $image->core()->native();

        foreach ($vipImage->getFields() as $name) {
            if (str_starts_with($name, 'exif-')) {
                $vipImage->remove($name);
            }
        }

        $image->setExif(new Collection());
        $image->core()->setNative($vipImage);

        return $image;
    }
}
