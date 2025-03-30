<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Rectangle;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\PointInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\TextModifier as GenericTextModifier;
use Intervention\Image\Typography\TextBlock;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Exception as VipsException;

class TextModifier extends GenericTextModifier implements SpecializedInterface
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws RuntimeException
     * @throws VipsException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $textBlock = new TextBlock($this->text);
        $fontProcessor = new FontProcessor();

        // decode text color
        $color = $this->driver()->handleInput($this->font->color());

        // build vips image with text
        $textBlockImage = $fontProcessor->textToVipsImage($this->text, $this->font, $color);

        // calculate block position
        $blockSize = $this->blockSize($textBlockImage);

        // calculate baseline
        $capImage = $fontProcessor->textToVipsImage('T', $this->font);
        $baseline = $capImage->height + $capImage->yoffset;

        // adjust block size
        switch ($this->font->valignment()) {
            case 'top':
                $blockSize->movePointsY($baseline * -1);
                $blockSize->movePointsY($textBlockImage->yoffset);
                $blockSize->movePointsY($capImage->height);
                break;

            case 'bottom':
                $lastLineImage = $fontProcessor->textToVipsImage((string) $textBlock->last(), $this->font);
                $blockSize->movePointsY($lastLineImage->height);
                $blockSize->movePointsY($baseline * -1);
                $blockSize->movePointsY($lastLineImage->yoffset);
                break;
        }

        // apply rotation
        $blockSize->rotate($this->font->angle());

        // extract block position
        $blockPosition = clone $blockSize->last();

        // apply text rotation if necessary
        $textBlockImage = $this->maybeRotateText($textBlockImage);

        // apply rotation offset to block position
        if ($this->font->angle() != 0) {
            $blockPosition->move(
                $textBlockImage->xoffset * -1,
                $textBlockImage->yoffset * -1
            );
        }

        if ($this->font->hasStrokeEffect()) {
            // decode stroke color
            $strokeColor = $this->driver()->handleInput($this->font->strokeColor());

            // build stroke text image if applicable
            $stroke = $fontProcessor->textToVipsImage($this->text, $this->font, $strokeColor);

            // apply rotation for stroke effect
            $stroke = $this->maybeRotateText($stroke);
        }

        if (!$image->isAnimated()) {
            $modified = $image->core()->first();

            if (isset($stroke)) {
                // draw stroke effect with offsets
                foreach ($this->strokeOffsets($this->font) as $offset) {
                    $modified = $this->placeTextOnFrame(
                        $stroke,
                        $modified,
                        $blockPosition->x() - $offset->x(),
                        $blockPosition->y() - $offset->y()
                    );
                }
            }

            // place text image on original image
            $modified = $this->placeTextOnFrame(
                $textBlockImage,
                $modified,
                $blockPosition->x(),
                $blockPosition->y()
            );

            $modified = $modified->native();
        } else {
            $frames = [];
            foreach ($image as $frame) {
                $modifiedFrame = $frame;

                if (isset($stroke)) {
                    // draw stroke effect with offsets
                    foreach ($this->strokeOffsets($this->font) as $offset) {
                        $modifiedFrame = $this->placeTextOnFrame(
                            $stroke,
                            $modifiedFrame,
                            $blockPosition->x() - $offset->x(),
                            $blockPosition->y() - $offset->y()
                        );
                    }
                }

                // place text image on original image
                $modifiedFrame = $this->placeTextOnFrame(
                    $textBlockImage,
                    $modifiedFrame,
                    $blockPosition->x(),
                    $blockPosition->y()
                );

                $frames[] = $modifiedFrame;
            }

            $modified = Core::replaceFrames($image->core()->native(), $frames);
        }

        $image->core()->setNative($modified);

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
     * Build size from given vips image
     *
     * @param VipsImage $blockImage
     * @return Rectangle
     */
    private function blockSize(VipsImage $blockImage): Rectangle
    {
        $imageSize = new Rectangle($blockImage->width, $blockImage->height, $this->position);
        $imageSize->align($this->font->alignment());
        $imageSize->valign($this->font->valignment());

        return $imageSize;
    }

    /**
     * Maybe rotate text image according to current font angle
     *
     * @param VipsImage $text
     * @throws VipsException
     * @return VipsImage
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

    /** @phpstan-ignore method.unused */
    private function debugPos(ImageInterface $image, PointInterface $position, Rectangle $size): void
    {
        // draw pos
        // @phpstan-ignore missingType.checkedException
        $image->drawCircle($position->x(), $position->y(), function (CircleFactory $circle): void {
            $circle->diameter(8);
            $circle->background('red');
        });

        // draw points of size
        foreach (array_chunk($size->toArray(), 2) as $point) {
            // @phpstan-ignore missingType.checkedException
            $image->drawCircle($point[0], $point[1], function (CircleFactory $circle): void {
                $circle->diameter(12);
                $circle->border('green');
            });
        }

        // draw size's pivot
        // @phpstan-ignore missingType.checkedException
        $image->drawCircle($size->pivot()->x(), $size->pivot()->y(), function (CircleFactory $circle): void {
            $circle->diameter(20);
            $circle->border('blue');
        });
    }
}
