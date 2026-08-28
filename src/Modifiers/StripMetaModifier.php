<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Modifiers;

use Intervention\Image\Collection;
use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\Exceptions\ModifierException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;
use Intervention\Image\Interfaces\SpecializedInterface;
use Jcupitt\Vips\Exception as VipsException;

class StripMetaModifier implements ModifierInterface, SpecializedInterface
{
    /**
     * Meta data fields libvips attaches to an image when it decodes one. The
     * color profile is deliberately not among them, stripping meta data keeps
     * it, in line with what the encoders of this driver do for their strip
     * parameter.
     */
    private const META_FIELDS = [
        'image-description',
        'iptc-data',
        'jpeg-thumbnail-data',
        'photoshop-data',
        'xmp-data',
    ];

    /**
     * Prefixes of meta data fields that libvips numbers or names after the tag
     * they hold, "exif-ifd0-Artist" or "png-comment-0-Software" for instance.
     */
    private const META_FIELD_PREFIXES = [
        'exif-',
        'png-comment-',
    ];

    /**
     * {@inheritdoc}
     *
     * @see ModifierInterface::apply()
     *
     * @throws ModifierException
     * @throws DriverException
     * @throws InvalidArgumentException
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $core = $image->core();

        if (!$core instanceof Core) {
            throw new ModifierException('Failed to strip meta data, image is not backed by ' . Core::class);
        }

        try {
            // work on a copy so the fields of an image shared with a clone stay put
            $native = $core->native()->copy();

            foreach ($native->getFields() as $field) {
                if ($this->isMetaField($field)) {
                    $native->remove($field);
                }
            }
        } catch (VipsException $e) {
            throw new ModifierException('Failed to strip meta data from image', previous: $e);
        }

        // removing the fields is not enough, libvips builds an EXIF block from
        // the image's core fields at save time. Mark the core so the encoders
        // tell the save to keep it out of the result as well. Both run before
        // setNative(), which can throw once it renders the pipeline to memory.
        $image->setExif(new Collection());
        $core->setMetaStripped();

        // this also clears the stashed source on purpose. Keeping it would let
        // a later resize reload the original file and put the fields back.
        $core->setNative($native);

        return $image;
    }

    /**
     * Determine whether the given vips field holds meta data.
     */
    private function isMetaField(string $field): bool
    {
        if (in_array($field, self::META_FIELDS, true)) {
            return true;
        }

        foreach (self::META_FIELD_PREFIXES as $prefix) {
            if (str_starts_with($field, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
