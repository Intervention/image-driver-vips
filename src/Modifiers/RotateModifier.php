<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\RotateModifier as GenericRotateModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

class RotateModifier extends GenericRotateModifier implements SpecializedInterface
{
    /**
     * Angles served by rot90/rot180/rot270. They expose no new area and
     * therefore paint no background, unlike the similarity() fallback.
     */
    private const ANGLES_WITHOUT_BACKGROUND = [0.0, 90.0, -270.0, 180.0, -180.0, -90.0, 270.0];

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws ModifierException
     * @throws StateException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // rot90/rot180/rot270/similarity require non-sequential reads of the
        // source. Sequentially-decoded sources fail with `out of order read`
        // when the encoder later walks the pipeline.
        Core::ensureInMemory($image->core());

        $angle = $this->rotationAngle();

        // similarity() needs an alpha channel to paint the areas it exposes.
        // Sources that decode to three bands (NativeObjectDecoder only adds
        // the channel to SRGB sources, so 16-bit ones keep three bands) get
        // theirs here, before the background color is exported, so that the
        // exported vector is sized to the bandcount similarity() will see.
        if (!in_array($angle, self::ANGLES_WITHOUT_BACKGROUND, true)) {
            $this->addAlpha($image);
        }

        $backgroundColor = $this->scaleToImage(
            $this->driver()->colorProcessor($image)->export($this->backgroundColor()),
            $image->core()->native(),
        );

        $frames = [];
        foreach ($image as $frame) {
            try {
                $native = match ($angle) {
                    0.0 => $frame->native(),
                    90.0, -270.0 => $frame->native()->rot90(),
                    180.0, -180.0 => $frame->native()->rot180(),
                    -90.0, 270.0 => $frame->native()->rot270(),
                    default => $this->rotate($frame->native(), $backgroundColor),
                };
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to rotate image',
                    previous: $e,
                );
            }

            $frames[] = $frame->setNative($native);
        }

        $image->core()->setNative(
            Core::replaceFrames($image->core()->native(), $frames),
        );

        return $image;
    }

    /**
     * @param array<float> $backgroundColor
     */
    private function rotate(VipsImage $vipsImage, array $backgroundColor): VipsImage
    {
        return $vipsImage->similarity([
            'angle' => $this->rotationAngle(), // TODO: check rotation direction
            'background' => $backgroundColor,
        ]);
    }

    /**
     * Add an alpha channel to the given image unless it already carries one.
     *
     * addalpha() picks the opaque value that goes with the interpretation of
     * the image, where a hardcoded 255 would leave 16-bit sources all but
     * fully transparent.
     *
     * @throws ModifierException
     */
    private function addAlpha(ImageInterface $image): void
    {
        $vipsImage = $image->core()->native();

        try {
            if ($vipsImage->hasAlpha()) {
                return;
            }

            $image->core()->setNative($vipsImage->addalpha());
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to add alpha channel',
                previous: $e,
            );
        }
    }

    /**
     * Scale the exported color to the value range of the given image.
     *
     * ColorProcessor::export() returns channel values on a 0-255 scale, but
     * libvips reads the background of similarity() on the scale that goes
     * with the interpretation of the image: 0-65535 for 16-bit sources and
     * 0-1 for scRGB ones. The ranges below are the ones libvips itself uses
     * to pick an opaque alpha value in addalpha().
     *
     * @param array<float> $color
     * @return array<float>
     */
    private function scaleToImage(array $color, VipsImage $vipsImage): array
    {
        $max = match ($vipsImage->interpretation) {
            Interpretation::RGB16, Interpretation::GREY16 => 65535,
            Interpretation::SCRGB => 1,
            default => 255,
        };

        if ($max === 255) {
            return $color;
        }

        return array_map(fn(float $value): float => $value / 255 * $max, $color);
    }
}
