<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Drivers\Vips\ColorProcessor;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Drivers\Vips\Source\BufferSource;
use Intervention\Image\Drivers\Vips\Source\PathSource;
use Intervention\Image\Drivers\Vips\Traits\CanNormalizeBands;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Intervention\Image\Modifiers\ResizeModifier as GenericResizeModifier;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

class ResizeModifier extends GenericResizeModifier implements SpecializedInterface
{
    use CanNormalizeBands;

    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\ModifierInterface::apply()
     *
     * @throws InvalidArgumentException
     * @throws VipsException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $resizeTo = $this->adjustedSize($image);
        $core = $image->core();
        $stash = $core instanceof Core ? $core->stashedSource() : null;

        if ($stash !== null && !$image->isAnimated()) {
            $core->setNative(
                $this->normalizeBands($this->thumbnailFromStash(
                    $stash,
                    $resizeTo,
                    $image->colorspace(),
                ))
            );

            return $image;
        }

        $options = [
            'height' => $resizeTo->height(),
            'size' => 'force',
            'no_rotate' => true,
        ];

        $exportProfile = ColorProcessor::thumbnailExportProfile($image->colorspace());
        if ($exportProfile !== null) {
            $options['export-profile'] = $exportProfile;
        }

        $image->core()->setNative(
            $image->core()->native()->thumbnail_image($resizeTo->width(), $options)
        );

        return $image;
    }

    /**
     * Return the size the modifier will resize to
     */
    protected function adjustedSize(ImageInterface $image): SizeInterface
    {
        return $image->size()->resize($this->width, $this->height);
    }

    /**
     * @throws VipsException
     */
    private function thumbnailFromStash(
        PathSource|BufferSource $stash,
        SizeInterface $resizeTo,
        ColorspaceInterface $colorspace,
    ): VipsImage {
        $options = [
            'height' => $resizeTo->height(),
            'size' => 'force',
            'no_rotate' => true,
        ];

        $interpretation = ColorProcessor::colorspaceToInterpretation($colorspace);
        if (in_array($interpretation, [Interpretation::CMYK, Interpretation::HSV], true)) {
            $options['export-profile'] = $interpretation;
        }

        // Note: stash->optionString is intentionally NOT passed here.
        // thumbnail* is single-frame; we already gated on !isAnimated().
        // Multi-page options like n=-1 also fail on single-frame loaders
        // such as pngload that do not recognise them.
        return $stash instanceof PathSource
            ? VipsImage::thumbnail($stash->path, $resizeTo->width(), $options)
            : VipsImage::thumbnail_buffer($stash->buffer, $resizeTo->width(), $options);
    }
}
