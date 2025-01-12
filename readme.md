# image-driver-vips
## libvips driver for Intervention Image 3

[![Latest Version](https://img.shields.io/packagist/v/intervention/image-driver-vips.svg)](https://packagist.org/packages/intervention/image-driver-vips)
[![Build Status](https://github.com/Intervention/image-driver-vips/actions/workflows/run-tests.yml/badge.svg)](https://github.com/Intervention/image-driver-vips/actions)
[![Monthly Downloads](https://img.shields.io/packagist/dm/intervention/image-driver-vips.svg)](https://packagist.org/packages/intervention/image-driver-vips/stats)

## WARNING: UNSTABLE

**The code is in a very early and experimental stage of development. Many
features are not yet implemented and tested. There may still be significant
changes. Therefore, it is not recommended to use the driver in production
environments.**

Driver to use [Intervention Image](https://github.com/Intervention/image) with
[libvips](https://github.com/libvips/libvips) which is a fast image processing
library with low memory needs. This driver combines the easy-to-use API of
Intervention Image with the performance of libvips.

## Requirements

- PHP >= 8.1

## Installation

You can easily install this library using [Composer](https://getcomposer.org).
Simply request the package with the following command:
    
```bash
composer require intervention/image-driver-vips
```

## Getting Started

The public [API](https://image.intervention.io/v3) of Intervention Image can be
used unchanged. The only [configuration](https://image.intervention.io/v3/basics/image-manager) that needs to be done is to ensure that
the `libvips` driver is used by `Intervention\Image\ImageManager`.

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

## Caveats

- Due to the technical characteristics of libvips, it is currently not possible
  to implement colour quantization via `ImageInterface::reduceColors()` as
  intended. However, there is a [pull request in
  libvips](https://github.com/libvips/php-vips/issues/256#issuecomment-2575872401)
  that enables this feature and may be integrated here in the future.

## Authors

This library was developed by [Oliver Vogel](https://intervention.io) and Thomas Picquet.

## License

Intervention Image Driver Vips is licensed under the [MIT License](LICENSE).
