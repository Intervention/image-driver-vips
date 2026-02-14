<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\FontProcessor;
use Intervention\Image\Exceptions\DirectoryNotFoundException;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\FileNotFoundException;
use Intervention\Image\Exceptions\FileNotReadableException;
use Intervention\Image\Exceptions\FilePointerException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Exceptions\StateException;
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
     * @throws InvalidArgumentException
     * @throws StateException
     * @throws DirectoryNotFoundException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FilePointerException
     * @throws DriverException
     * @throws ModifierException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $textBlock = new TextBlock($this->text);
        $fontProcessor = new FontProcessor();

        // decode text color
        $color = $this->driver()->handleColorInput($this->font->color());

        // build vips image with text
        $textBlockImage = $fontProcessor->textToVipsImage($this->text, $this->font, $color);

        // calculate block position
        $blockSize = $this->blockSize($textBlockImage);

        // calculate baseline
        $capImage = $fontProcessor->textToVipsImage('T', $this->font);
        $baseline = $capImage->height + $capImage->yoffset;

        // adjust block size
        switch ($this->font->alignmentVertical()) {
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
        try {
            $textBlockImage = $this->maybeRotateText($textBlockImage);
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to rotate text block',
                previous: $e
            );
        }

        // apply rotation offset to block position
        if ($this->font->angle() != 0) {
            $blockPosition->move(
                $textBlockImage->xoffset * -1,
                $textBlockImage->yoffset * -1
            );
        }

        if ($this->font->hasStrokeEffect()) {
            // decode stroke color
            $strokeColor = $this->driver()->handleColorInput($this->font->strokeColor());

            // build stroke text image if applicable
            $stroke = $fontProcessor->textToVipsImage($this->text, $this->font, $strokeColor);

            // apply rotation for stroke effect
            try {
                $stroke = $this->maybeRotateText($stroke);
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to rotate text block',
                    previous: $e
                );
            }
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
     * @throws ModifierException
     */
    private function blockSize(VipsImage $blockImage): Rectangle
    {
        try {
            $imageSize = new Rectangle($blockImage->width, $blockImage->height, $this->position);
            $imageSize->alignHorizontally($this->font->alignmentHorizontal());
            $imageSize->alignVertically($this->font->alignmentVertical());
        } catch (InvalidArgumentException $e) {
            throw new ModifierException('Failed to build font size', previous: $e);
        }

        return $imageSize;
    }

    /**
     * Maybe rotate text image according to current font angle
     *
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

    /** @phpstan-ignore method.unused */
    private function debugPos(ImageInterface $image, PointInterface $position, Rectangle $size): void
    {
        // draw pos
        $image->drawCircle(function (CircleFactory $circle) use ($position): void {
            $circle->diameter(8);
            $circle->background('red');
            $circle->at(...$position);
        });

        // draw points of size
        foreach (array_chunk($size->toArray(), 2) as $point) {
            $image->drawCircle(function (CircleFactory $circle) use ($point): void {
                $circle->diameter(12);
                $circle->border('green');
                $circle->at(...$point);
            });
        }

        // draw size's pivot
        $image->drawCircle(function (CircleFactory $circle) use ($size): void {
            $circle->diameter(20);
            $circle->border('blue');
            $circle->at(...$size->pivot());
        });
    }
}
