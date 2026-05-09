<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Source;

/**
 * The original file path the image was decoded from, plus the libvips
 * option-string the decoder used. Stashed on Core after a clean decode so
 * that resize-family modifiers can swap to libvips' combined load+resize
 * `thumbnail()` instead of `thumbnail_image()`.
 */
final readonly class PathSource
{
    public function __construct(
        public string $path,
        public string $optionString = '',
    ) {
        //
    }

    /**
     * Build the path argument including the option-string suffix that
     * libvips file loaders accept (e.g. "/foo/bar.jpg[n=-1]").
     */
    public function pathWithOptions(): string
    {
        return $this->optionString === '' ? $this->path : $this->path . '[' . $this->optionString . ']';
    }
}
