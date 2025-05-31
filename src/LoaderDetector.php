<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Format;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\FFI;

class LoaderDetector
{
    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * List of available vips-loaders
     *
     * @var array<string>
     */
    protected array $loaders = [];

    /**
     * Private constructor, use self::create()
     *
     * @return void
     */
    private function __construct()
    {
        $base = FFI::gobject()->g_type_from_name("VipsForeignLoad"); // @phpstan-ignore-line
        FFI::vips()->vips_type_map($base, $this->collectLoaders(...), null, null); // @phpstan-ignore-line

        // normalize loader names
        $this->loaders = array_map(function (string $name): ?string {
            preg_match("/^(?P<identifier>[a-z0-9]+)load_/", $name, $matches);
            return array_key_exists('identifier', $matches) ? $matches['identifier'] : null;
        }, $this->loaders);

        // filter out null values
        $this->loaders = array_filter($this->loaders, fn(?string $identifier): bool => !is_null($identifier));

        // make unique
        $this->loaders = array_unique($this->loaders, SORT_STRING);
    }

    /**
     * Create instance via singleton variable
     */
    public static function create(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Return array of available vips-loaders
     *
     * @return array<string>
     */
    public function loaders(): array
    {
        return $this->loaders;
    }

    /**
     * Return array of available formats detected from vips-loaders
     *
     * @return array<Format>
     */
    public function formats(): array
    {
        $formats = [];

        foreach ($this->loaders as $loader) {
            switch ($loader) {
                case 'jpeg':
                    $formats[] = Format::JPEG;
                    break;

                case 'gif':
                    $formats[] = Format::GIF;
                    break;

                case 'png':
                    $formats[] = Format::PNG;
                    break;

                case 'heif':
                    $formats[] = Format::AVIF;
                    $formats[] = Format::HEIC;
                    break;

                case 'magick':
                    $formats[] = Format::BMP;
                    break;

                case 'webp':
                    $formats[] = Format::WEBP;
                    break;

                case 'tiff':
                    $formats[] = Format::TIFF;
                    break;

                case 'jp2k':
                    $formats[] = Format::JP2;
                    break;
            }
        }

        return $formats;
    }

    /**
     * Collect loaders
     *
     * @throws VipsException
     */
    private function collectLoaders(string $type): void
    {
        $name = FFI::vips()->vips_nickname_find($type); // @phpstan-ignore-line
        $this->loaders[] = $name;
        FFI::vips()->vips_type_map($type, $this->collectLoaders(...), null, null); // @phpstan-ignore-line
    }
}
