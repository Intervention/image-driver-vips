<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\TextModifier as GenericTextModifier;
use Jcupitt\Vips\BlendMode;

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

            // place line on image
            $image->core()->setNative(
                $image->core()->native()->composite($text, BlendMode::OVER, [
                    'x' => $line->position()->x(),
                    'y' => $line->position()->y() - $height,
                ])
            );
        }

        return $image;
    }
}
