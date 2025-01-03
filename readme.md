# image-driver-vips
## libvips driver for Intervention Image

[![Latest Version](https://img.shields.io/packagist/v/intervention/image-driver-vips.svg)](https://packagist.org/packages/intervention/image-driver-vips)
[![Build Status](https://github.com/Intervention/image-driver-vips/actions/workflows/run-tests.yml/badge.svg)](https://github.com/Intervention/image-driver-vips/actions)
[![Monthly Downloads](https://img.shields.io/packagist/dm/intervention/image-driver-vips.svg)](https://packagist.org/packages/intervention/image-driver-vips/stats)

## WARNING: UNSTABLE

**The code is in a very early and experimental stage of development. Many
features are not yet implemented and tested. There may still be significant
changes. Therefore, it is not recommended to use the driver in production
environments.**

Driver to use [libvips](https://github.com/libvips/libvips) with [Intervention
Image](https://github.com/Intervention/image). Combines the easy-to-use API of
Intervention Image with the technical performance of libvips.

## Requirements

- PHP >= 8.1
- Intervention Image >= 3

## Installation

You can easily install this library using [Composer](https://getcomposer.org).
Just request the package with the following command:
    
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

## Development Status

The library is under development and not yet complete. Here is an overview of
the features that have already been implemented.

### Feature Status

| Feature | Status |
| - | - |
| ImageManager::read() | ✅ |
| ImageManager::create() | ✅ |
| ImageManager::animate() | ✅ |
| Image::width() | ✅ |
| Image::height() | ✅ |
| Image::size() | ✅ |
| Image::resize() | ✅ |
| Image::resizeDown() | ✅ |
| Image::scale() | ✅ |
| Image::scaleDown() | ✅ |
| Image::cover() | ✅ |
| Image::coverDown() | ✅ |
| Image::pad() | ❌ |
| Image::contain() | ✅ |
| Image::crop() | ✅ |
| Image::resizeCanvas() | ❌ |
| Image::resizeCanvasRelative() | ❌ |
| Image::trim() | 🪲 |
| Image::place() | 🪲 |
| Image::brightness() | ✅ |
| Image::contrast() | ✅ |
| Image::gamma() | ✅ |
| Image::colorize() | ❌ |
| Image::greyscale() | ✅ |
| Image::flip() | ✅ |
| Image::flop() | ✅ |
| Image::rotate() | 🪲 |
| Image::orient() | ✅ |
| Image::blur() | ✅ |
| Image::sharpen() | ✅ |
| Image::invert() | ✅ |
| Image::pixelate() | 🪲 |
| Image::reduceColors() | ❌ |
| Image::text() | ❌ |
| Image::fill() | ✅ |
| Image::drawPixel() | ❌ |
| Image::drawEllipse() | ✅ |
| Image::drawCircle() | ✅ |
| Image::drawLine() | ❌ |
| Image::drawPolygon() | ❌ |
| Image::drawBezier() | ❌ |
| Image::resolution() | ✅ |
| Image::setResolution() | ✅ |
| Image::exif() | ✅ |
| Image::pickColor() | ✅ |
| Image::pickColors() | ✅ |
| Image::colorspace() | ✅ |
| Image::setColorspace() | ✅ |
| Image::profile() | ✅ |
| Image::setProfile() | ✅ |
| Image::removeProfile() | ✅ |
| Image::blendingColor() | ✅ |
| Image::setBlendingColor() | ✅ |
| Image::blendTransparency() | ❌ |
| Image::isAnimated() | ✅ |
| Image::count() | ✅ |
| Image::sliceAnimation() | ✅ |
| Image::loops() | ✅ |
| Image::setLoops() | ✅ |
| Image::removeAnimation() | ✅ |
| Image::encode() | ✅ |
| Image::encodeByMediaType() | ✅ |
| Image::encodeByPath() | ✅ |
| Image::encodeByExtension() | ✅ |
| Image::save() | ✅ |
| Image::toJpeg() | ✅ |
| Image::toWebp() | ✅ |
| Image::toPng() | ✅ |
| Image::toGif() | ✅ |
| Image::toBitmap() | ✅ |
| Image::toAvif() | ✅ |
| Image::toTiff() | ✅ |
| Image::toJpeg2000() | ✅ |
| Image::toHeic() | ✅ |
| Image::supports() | 🪲 |

✅ Integrated
🪲 Integrated but has issues
🚧 Partly integrated
❌ Not (yet) integrated

## Contributing

Check out the [documentation](https://github.com/Intervention/image/blob/develop/CONTRIBUTING.md)

```bash
composer test
```
