<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Source;

/**
 * The original buffer the image was decoded from, plus the libvips
 * option-string the decoder used. Stashed on Core after a clean decode so
 * that resize-family modifiers can swap to libvips' combined load+resize
 * `thumbnail_buffer()` instead of `thumbnail_image()`.
 */
final readonly class BufferSource
{
    public function __construct(
        public string $buffer,
        public string $optionString = '',
    ) {
    }
}
