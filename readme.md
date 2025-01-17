# libvips driver for Intervention Image 3

[![Latest Version](https://img.shields.io/packagist/v/intervention/image-driver-vips.svg)](https://packagist.org/packages/intervention/image-driver-vips)
[![Build Status](https://github.com/Intervention/image-driver-vips/actions/workflows/run-tests.yml/badge.svg)](https://github.com/Intervention/image-driver-vips/actions)
[![Monthly Downloads](https://img.shields.io/packagist/dm/intervention/image-driver-vips.svg)](https://packagist.org/packages/intervention/image-driver-vips/stats)
[![Support me on Ko-fi](https://raw.githubusercontent.com/Intervention/image-driver-vips/develop/.github/images/support.svg)](https://ko-fi.com/interventionphp)

## WARNING: UNSTABLE

**The code is in beta stage and is currently finalised. Therefore, it is not
recommended to use the driver in production environments.**

Intervention Image's official driver to use [Intervention
Image](https://github.com/Intervention/image) with
[libvips](https://github.com/libvips/libvips). libvips is a fast, low-memory
image processing library that outperforms the standard PHP image extensions GD
and Imagick. This package makes it easy to utilize the power of libvips in your
project while taking advantage of Intervention Image's user-friendly and
easy-to-use API.

## Installation

You can easily install this library using [Composer](https://getcomposer.org).
Simply request the package with the following command:
    
```bash
composer require intervention/image-driver-vips
```

## Getting Started

The public [API](https://image.intervention.io/v3) of Intervention Image can be
used unchanged. The only [configuration](https://image.intervention.io/v3/basics/image-manager) that needs to be done is to ensure that
`Intervention\Image\Drivers\Vips\Driver` by this library is used by `Intervention\Image\ImageManager`.

## Code Examples

```php
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Vips\Driver as VipsDriver;

// create image manager with vips driver
$manager = ImageManager::withDriver(VipsDriver::class);

// open an image file
$image = $manager->read('images/example.gif');

// resize image instance
$image->resize(height: 300);

// insert a watermark
$image->place('images/watermark.png');

// encode edited image
$encoded = $image->toJpg();

// save encoded image
$encoded->save('images/example.jpg');
```

## Requirements

- PHP >= 8.1

## Caveats

- Due to the technical characteristics of libvips, it is currently **not possible**
  to implement color quantization via `ImageInterface::reduceColors()` as
  intended. However, there is a [pull request in
  libvips](https://github.com/libvips/php-vips/issues/256#issuecomment-2575872401)
  that enables this feature and it may be integrated here the future as well.

- With PHP on macOS, font files are not recognized in the
  `ImageInterface::text()` call because Quartz as a rendering engine does not
  allow font files to be loaded at runtime via the fontconfig API.

## Authors

This library was developed by [Oliver Vogel](https://intervention.io) and Thomas Picquet.

## License

Intervention Image Driver Vips is licensed under the [MIT License](LICENSE).
