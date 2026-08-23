<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\TrimModifier as GenericTrimModifier;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Interpretation;

class TrimModifier extends GenericTrimModifier implements SpecializedInterface
{
    public function __construct(public int $tolerance = 40)
    {
        parent::__construct($tolerance);
    }

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws NotSupportedException
     * @throws ModifierException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        if ($image->isAnimated()) {
            throw new NotSupportedException('Trim modifier cannot be applied to animated images.');
        }

        $core = Core::ensureInMemory($image->core());
        $native = $core->native();
        $width = $native->width;
        $height = $native->height;
        $maxThreshold = match ($image->core()->native()->format) {
            BandFormat::USHORT => 65535,
            BandFormat::FLOAT => 1,
            default => 255,
        };

        foreach ($this->cornerColors($image) as $point) {
            try {
                $trim = $native->find_trim([
                    'threshold' => min($this->tolerance + 10, $maxThreshold),
                    'background' => $point,
                ]);

                // An empty box means the whole remaining image matches the background color of
                // the current corner. The corner colors are read from the original image, so a
                // later corner may well match an area that an earlier corner has already
                // isolated. That area is content and must be kept.
                if ($trim['width'] === 0 || $trim['height'] === 0) {
                    if ($native->width < $width || $native->height < $height) {
                        continue;
                    }

                    // Nothing but background left. Return a single pixel, which is what the
                    // other drivers do in this case.
                    $native = $native->crop(0, 0, 1, 1);

                    break;
                }

                $native = $native->crop($trim['left'], $trim['top'], $trim['width'], $trim['height']);
            } catch (VipsException $e) {
                throw new ModifierException('Failed to trim image', previous: $e);
            }
        }

        $image->core()->setNative($native);

        return $image;
    }

    /**
     * Get the colors of the four corners of the image.
     *
     * @return array<array<float>>
     */
    private function cornerColors(ImageInterface $image): array
    {
        $size = $image->size();
        $native = $image->core()->native();

        return array_map(function (array $nativeColor) use ($native): array {
            return array_slice($nativeColor, 0, $native->interpretation === Interpretation::CMYK ? 4 : 3);
        }, [
            $native->getpoint(0, 0),
            $native->getpoint($size->width() - 1, 0),
            $native->getpoint(0, $size->height() - 1),
            $native->getpoint($size->width() - 1, $size->height() - 1),
        ]);
    }
}
