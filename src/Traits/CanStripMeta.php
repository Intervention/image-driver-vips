<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Traits;

use Intervention\Image\Drivers\Vips\Core;
use Intervention\Image\Exceptions\StateException;
use Intervention\Image\Interfaces\DriverInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Jcupitt\Vips\Config as VipsConfig;
use Jcupitt\Vips\ForeignKeep;

trait CanStripMeta
{
    /**
     * Encoders using this trait are driver specialized, the driver carries the
     * global strip config. Declared so a class that is not gets caught at load
     * time rather than on the first encode.
     */
    abstract public function driver(): DriverInterface;

    /**
     * Return the meta data options of libvips' save operation for the given image.
     *
     * libvips builds an EXIF block from the image's core fields at save time,
     * whether or not the image carries one, so the save operation is the only
     * place where meta data can be kept out of the encoded result. Removing
     * the fields from the image beforehand does not reach it, which is why an
     * image stripped by StripMetaModifier is reported by its core here.
     *
     * @throws StateException
     * @return array{keep: int}|array{strip: bool}
     */
    private function metaOptions(ImageInterface $image, ?bool $strip = null): array
    {
        $core = $image->core();

        $strip = ($strip ?? false)
            || $this->driver()->config()->strip
            || ($core instanceof Core && $core->metaStripped());

        if (!VipsConfig::atLeast(8, 15)) {
            return ['strip' => $strip];
        }

        // GAINMAP only exists from libvips 8.18, passing it to 8.15-8.17 spams
        // stderr with a GLib-GObject-CRITICAL on every encode
        $keepAll = VipsConfig::atLeast(8, 18)
            ? ForeignKeep::ALL
            : ForeignKeep::ALL & ~ForeignKeep::GAINMAP;

        return ['keep' => $strip ? ForeignKeep::ICC : $keepAll];
    }
}
