<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Source\BufferSource;
use Intervention\Image\Drivers\Vips\Source\PathSource;
use Intervention\Image\Drivers\Vips\Traits\CanNormalizeBands;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\CoverModifier as GenericCoverModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interesting;
use Jcupitt\Vips\Interpretation;

class CoverModifier extends GenericCoverModifier implements SpecializedInterface
{
    use CanNormalizeBands;

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws ModifierException
     * @throws DriverException
     * @throws InvalidArgumentException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        try {
            $crop = $this->cropSize($image);
            $resize = $this->resizeSize($crop);
        } catch (InvalidArgumentException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to calculate target size',
                previous: $e
            );
        }

        $colorspace = $image->colorspace();
        $core = $image->core();
        $stash = $core instanceof Core ? $core->stashedSource() : null;
        $isCenter = Alignment::create($this->alignment) === Alignment::CENTER;

        // Fastest path: stash-based thumbnail* (combines load + resize + crop)
        if ($stash !== null && $isCenter && !$image->isAnimated()) {
            try {
                $native = $this->normalizeBands(
                    $this->coverFromStash($stash, $resize, $colorspace)
                );
            } catch (VipsException $e) {
                throw new ModifierException(
                    'Failed to apply ' . self::class . ', unable to process resizing',
                    previous: $e
                );
            }

            $core->setNative(Core::syncPageHeight($native));

            return $image;
        }

        // Single-frame path: skip the per-frame loop
        if (!$image->isAnimated()) {
            $native = $this->cropResizeFrame(
                $core->first(),
                $crop,
                $resize,
                $colorspace,
            );
            $core->setNative(Core::syncPageHeight($native));

            return $image;
        }

        // Animated path
        $frames = [];
        foreach ($image as $frame) {
            $native = $this->cropResizeFrame($frame, $crop, $resize, $colorspace);
            $frames[] = $frame->setNative($native);
        }

        $core->setNative(
            Core::replaceFrames($core->native(), $frames)
        );

        return $image;
    }

    /**
     * @throws VipsException
     */
    private function coverFromStash(
        PathSource|BufferSource $stash,
        SizeInterface $resize,
        ColorspaceInterface $colorspace,
    ): VipsImage {
        $options = [
            'height' => $resize->height(),
            'crop' => Interesting::CENTRE,
            'no_rotate' => true,
        ];

        $interpretation = ColorProcessor::colorspaceToInterpretation($colorspace);
        if (in_array($interpretation, [Interpretation::CMYK, Interpretation::HSV], true)) {
            $options['export-profile'] = $interpretation;
        }

        // stash->optionString intentionally not forwarded; thumbnail* is
        // single-frame and n=-1 fails on single-frame loaders like pngload.
        return $stash instanceof PathSource
            ? VipsImage::thumbnail($stash->path, $resize->width(), $options)
            : VipsImage::thumbnail_buffer($stash->buffer, $resize->width(), $options);
    }

    /**
     * @throws ModifierException
     */
    private function cropResizeFrame(
        FrameInterface $frame,
        SizeInterface $cropSize,
        SizeInterface $resizeSize,
        ColorspaceInterface $colorspace
    ): VipsImage {
        $options = [
            'height' => $resizeSize->height(),
            'size' => 'force',
            'no_rotate' => true,
        ];

        $exportProfile = ColorProcessor::thumbnailExportProfile($colorspace);
        if ($exportProfile !== null) {
            $options['export-profile'] = $exportProfile;
        }

        try {
            return $frame->native()->crop(
                $cropSize->pivot()->x(),
                $cropSize->pivot()->y(),
                $cropSize->width(),
                $cropSize->height()
            )->thumbnail_image($resizeSize->width(), $options);
        } catch (VipsException $e) {
            throw new ModifierException(
                'Failed to apply ' . self::class . ', unable to process resizing',
                previous: $e
            );
        }
    }
}
