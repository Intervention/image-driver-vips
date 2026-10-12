<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\PointInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\InsertModifier as GenericInsertModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image as VipsImage;

class InsertModifier extends GenericInsertModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws StateException
     * @throws DriverException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $watermark = $this->driver()->decodeImage($this->image);
        $position = $this->position($image, $watermark);

        if (!$this->overlap($image->size(), $watermark->size(), $position)) {
            return $image;
        }

        // A libvips sequential source may only be read once, in order. The
        // element is folded into the target's pipeline by reference, so it is
        // read again every time that pipeline is evaluated: inserting the same
        // decoded image twice, or encoding the result twice, fails with "out
        // of order read". Materialising the element on its own core spends
        // that single read here, and leaves every later evaluation reading
        // from memory. Decoded images are shared by reference, so an image
        // inserted many times only pays for this once.
        Core::ensureInMemory($watermark->core());

        $elementNative = $watermark->core()->native();

        // An element without alpha, at full opacity, is copied into the image
        // with insert(), anything else is blended with composite2().
        $blend = $elementNative->hasAlpha() || $this->transparency < 1;

        if (!$elementNative->hasAlpha()) {
            $elementNative = $this->fitElement($elementNative, $image->core()->native(), $blend);
        }

        if ($this->transparency < 1) {
            $elementNative = $elementNative->multiply([
                ...array_fill(0, $elementNative->bands - 1, 1.0),
                $this->transparency,
            ]);
        }

        if (!$image->isAnimated()) {
            $watermarked = $this->placeElement(
                $elementNative,
                $position,
                $image->core()->first(),
                $blend,
            )->native();
        } else {
            // Materialise the watermark so every frame composites against an
            // in-memory image. Without this, the pipeline references the same
            // lazy source N times, which breaks sequential libvips loaders
            // (e.g. pngload_buffer) that cannot be re-read out of order.
            $elementNative = $elementNative->copyMemory();

            $frames = [];
            foreach ($image as $frame) {
                $frames[] = $this->placeElement(
                    $elementNative,
                    $position,
                    $frame,
                    $blend,
                );
            }

            $watermarked = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($watermarked);

        return $image;
    }

    /**
     * Bring an element without alpha to the colour space of the image, and
     * give it an alpha band when it is blended or when the image has one.
     * insert() copies the values of the element as they are: a CMYK element
     * would land as raw ink values, with K in the alpha band, and a 16-bit
     * one would not fit the bands of the image. composite2() converts colours
     * itself, but a 16-bit element would make its result 16-bit. addalpha()
     * picks the opaque value that goes with the interpretation, 255, 65535 or
     * 1.0.
     */
    private function fitElement(VipsImage $element, VipsImage $image, bool $blend): VipsImage
    {
        if ($element->interpretation !== $image->interpretation) {
            try {
                $element = $element->colourspace($image->interpretation);
            } catch (VipsException) {
                // Interpretations like multiband, matrix or rgb are tags rather
                // than colour spaces, and colourspace() has no route to them.
                // The element is placed as it is then.
                return $blend ? $element->addalpha() : $element;
            }
        }

        return $blend || $image->hasAlpha() ? $element->addalpha() : $element;
    }

    /**
     * Place element at position on frame.
     */
    private function placeElement(
        VipsImage $element,
        PointInterface $position,
        FrameInterface $frame,
        bool $blend,
    ): FrameInterface {
        if ($blend) {
            $element = $element->embed(
                $position->x(),
                $position->y(),
                $frame->size()->width(),
                $frame->size()->height(),
                [
                    'extend' => Extend::BACKGROUND,
                    'background' => array_fill(0, $element->bands, 0),
                ],
            );

            $frame->setNative(
                $frame->native()->composite2(
                    $element,
                    BlendMode::OVER,
                ),
            );
        } else {
            $frame->setNative(
                $frame->native()->insert(
                    $element,
                    $position->x(),
                    $position->y(),
                ),
            );
        }

        return $frame;
    }

    /**
     * Determine if the canvas and watermark areas overlap.
     */
    private function overlap(SizeInterface $canvas, SizeInterface $watermark, PointInterface $position): bool
    {
        if ($position->x() >= $canvas->width()) {
            return false;
        }

        if ($position->y() >= $canvas->height()) {
            return false;
        }

        if ($position->x() + $watermark->width() <= 0) {
            return false;
        }

        if ($position->y() + $watermark->height() <= 0) {
            return false;
        }

        return true;
    }
}
