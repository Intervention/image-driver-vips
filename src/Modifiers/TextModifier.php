<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\TextModifier as GenericTextModifier;

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
            $text = $fontProcessor->textToVipsImage((string) $line, $this->font, $color);
            $image->core()->setNative(
                $image->core()->native()->composite($text, 'over', [
                    'x' => $line->position()->x(),
                    'y' => $line->position()->y(),
                ])
            );
        }

        return $image;
    }
}
