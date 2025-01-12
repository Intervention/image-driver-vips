<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\TextModifier as GenericTextModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Exception as VipsException;

class TextModifier extends GenericTextModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     * @throws RuntimeException
     * @throws VipsException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $fontProcessor = new FontProcessor();
        $color = $this->driver()->handleInput($this->font->color());
        $strokeColor = $this->driver()->handleInput($this->font->strokeColor());
        $lines = $fontProcessor->textBlock($this->text, $this->font, $this->position);

        foreach ($lines as $line) {
            // build vips image from text
            $text = $fontProcessor->textToVipsImage((string) $line, $this->font, $color);

            // original line height from vips image before rotation
            $height = $text->height;

            // apply rotation
            $text = $this->maybeRotateText($text);

            if ($this->font->hasStrokeEffect()) {
                // build stroke text image if applicable
                $stroke = $fontProcessor->textToVipsImage((string) $line, $this->font, $strokeColor);

                // original line height from vips image before rotation
                $strokeHeight = $stroke->height;

                // apply rotation for stroke effect
                $stroke = $this->maybeRotateText($stroke);
            }

            if (!$image->isAnimated()) {
                $modified = $image->core()->first();

                if (isset($stroke) && isset($strokeHeight)) {
                    // draw stroke effect with offsets
                    foreach ($this->strokeOffsets($this->font) as $offset) {
                        $modified = $this->placeTextOnFrame(
                            $stroke,
                            $modified,
                            $line->position()->x() - $offset->x(),
                            $line->position()->y() - $strokeHeight - $offset->y(),
                        );
                    }
                }

                // place text image on original image
                $modified = $this->placeTextOnFrame(
                    $text,
                    $modified,
                    $line->position()->x(),
                    $line->position()->y() - $height,
                );

                $modified = $modified->native();
            } else {
                $frames = [];
                foreach ($image as $frame) {
                    $modifiedFrame = $frame;
                    if (isset($stroke) && isset($strokeHeight)) {
                        // draw stroke effect with offsets
                        foreach ($this->strokeOffsets($this->font) as $offset) {
                            $modifiedFrame = $this->placeTextOnFrame(
                                $stroke,
                                $modifiedFrame,
                                $line->position()->x() - $offset->x(),
                                $line->position()->y() - $strokeHeight - $offset->y(),
                            );
                        }
                    }
                    // place text image on original image
                    $modifiedFrame = $this->placeTextOnFrame(
                        $text,
                        $modifiedFrame,
                        $line->position()->x(),
                        $line->position()->y() - $height,
                    );

                    $frames[] = $modifiedFrame;
                }

                $modified = Core::replaceFrames($image->core()->native(), $frames);
            }
            $image->core()->setNative($modified);
        }

        return $image;
    }

    /**
     * Place given text image at given position on given frame
     *
     * @param VipsImage $text
     * @param FrameInterface $frame
     * @param int $x
     * @param int $y
     * @return FrameInterface
     */
    private function placeTextOnFrame(VipsImage $text, FrameInterface $frame, int $x, int $y): FrameInterface
    {
        $frame->setNative(
            $frame->native()->composite($text, BlendMode::OVER, ['x' => $x, 'y' => $y])
        );

        return $frame;
    }

    /**
     * Maybe rotate text image according to current font angle
     *
     * @param VipsImage $text
     * @return VipsImage
     * @throws VipsException
     */
    private function maybeRotateText(VipsImage $text): VipsImage
    {
        return match ($this->font->angle()) {
            0.0 => $text,
            90.0, -270.0 => $text->rot90(),
            180.0, -180.0 => $text->rot180(),
            -90.0, 270.0 => $text->rot270(),
            default => $text->similarity(['angle' => $this->font->angle()]),
        };
    }
}
