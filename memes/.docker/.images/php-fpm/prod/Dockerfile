FROM php:7.4-fpm

RUN set -ex \
   && apt-get update && apt-get --allow-releaseinfo-change update && apt-get install -y git

COPY --from=composer /usr/bin/composer /usr/bin/composer

CMD ["php-fpm"]