FROM php:8.1-cli

# install dependencies
RUN apt update \
        && apt install -y --no-install-recommends \
            libvips42 \
            libffi-dev \
            libexif-dev \
            git \
            zip \
        && pecl install xdebug \
        && docker-php-ext-enable \
            xdebug \
        && docker-php-ext-install \
            exif \
            ffi \
        && apt-get clean

# ffi config
COPY --link docker/ffi.ini /usr/local/etc/php/conf.d/10-ffi.ini

# install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
