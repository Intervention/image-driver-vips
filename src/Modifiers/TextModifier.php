<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\TextModifier as GenericTextModifier;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Image as VipsImage;

class TextModifier extends GenericTextModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $fontProcessor = new FontProcessor();
        $color = $this->driver()->handleInput($this->font->color());
        $lines = $fontProcessor->textBlock($this->text, $this->font, $this->position);

        foreach ($lines as $line) {
            // build vips image from text
            $text = $fontProcessor->textToVipsImage((string) $line, $this->font, $color);

            // original line height from vips image
            $height = $text->height;

            // apply rotation
            $text = match ($this->font->angle()) {
                0 => $text,
                90.0, -270.0 => $text->rot90(),
                180.0, -180.0 => $text->rot180(),
                -90.0, 270.0 => $text->rot270(),
                default => $text->similarity(['angle' => $this->font->angle()]),
            };

            if (!$image->isAnimated()) {
                // place line on image
                $modified = $this->placeTextOnFrame(
                    $text,
                    $image->core()->first(),
                    $line->position()->x(),
                    $line->position()->y() - $height,
                )->native();
            } else {
                $frames = [];
                foreach ($image as $frame) {
                    $frames[] = $this->placeTextOnFrame(
                        $text,
                        $frame,
                        $line->position()->x(),
                        $line->position()->y() - $height,
                    );
                }

                $modified = Core::replaceFrames($image->core()->native(), $frames);
            }
        }

        $image->core()->setNative($modified);

        return $image;
    }

    private function placeTextOnFrame(VipsImage $text, FrameInterface $frame, int $x, int $y): FrameInterface
    {
        $frame->setNative(
            $frame->native()->composite($text, BlendMode::OVER, ['x' => $x, 'y' => $y])
        );

        return $frame;
    }
}
