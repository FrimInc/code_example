FROM php:7.4-fpm

RUN apt-get update && apt-get --allow-releaseinfo-change update && apt-get install -y \
   git \
    && pecl install xdebug-3.0.0 \
    && docker-php-ext-enable xdebug \

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY ./30-xdebug.ini /usr/local/etc/php/conf.d/remote-xdebug.ini

CMD ["php-fpm"]