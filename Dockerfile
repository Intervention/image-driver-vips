FROM php:8.3-cli

ARG LIBVIPS_VERSION=8.18.1

# install dependencies
RUN apt update \
        && apt install -y --no-install-recommends \
            build-essential \
            ninja-build \
            pkg-config \
            python3-pip \
            libglib2.0-dev \
            libexpat1-dev \
            libjpeg62-turbo-dev \
            libpng-dev \
            libtiff-dev \
            libwebp-dev \
            liblcms2-dev \
            libffi-dev \
            libexif-dev \
            libheif-dev \
            libheif-plugin-aomenc \
            libheif-plugin-x265 \
            libcgif-dev \
            libimagequant-dev \
            libopenjp2-7-dev \
            librsvg2-dev \
            libpoppler-glib-dev \
            libfftw3-dev \
            libarchive-dev \
            libhwy-dev \
            libmagickcore-dev \
            libpango1.0-dev \
            libcfitsio-dev \
            libmatio-dev \
            libopenexr-dev \
            git \
            unzip \
            zip \
        && pip3 install meson --break-system-packages \
        && curl -fsSL https://github.com/libvips/libvips/releases/download/v${LIBVIPS_VERSION}/vips-${LIBVIPS_VERSION}.tar.xz -o /tmp/vips.tar.xz \
        && tar xf /tmp/vips.tar.xz -C /tmp \
        && meson setup /tmp/vips-${LIBVIPS_VERSION}/build /tmp/vips-${LIBVIPS_VERSION} --libdir=lib --buildtype=release -Dintrospection=disabled \
        && meson compile -C /tmp/vips-${LIBVIPS_VERSION}/build \
        && meson install -C /tmp/vips-${LIBVIPS_VERSION}/build \
        && ldconfig \
        && rm -rf /tmp/vips* \
        && pecl install xdebug \
        && docker-php-ext-enable \
            xdebug \
        && docker-php-ext-install \
            exif \
            ffi \
        && apt-get clean

# ffi config
RUN echo "zend.max_allowed_stack_size=-1\nffi.enable=true" > /usr/local/etc/php/conf.d/10-ffi.ini

# install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# setup entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
