<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\GammaModifier as GenericGammaModifier;
use Jcupitt\Vips\Exception as VipsException;

class GammaModifier extends GenericGammaModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws ModifierException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        try {
            $native = $image->core()->native()->gamma([
                'exponent' => $this->gamma
            ]);
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to adjust image gamma value',
                previous: $e
            );
        }

        $image->core()->setNative($native);

        return $image;
    }
}
