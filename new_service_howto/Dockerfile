FROM php:7.4.4-fpm-alpine3.11 AS {{service-name}}-php

RUN apk add --no-cache --virtual .persistent-deps \
		git \
		postgresql-dev \
		icu-libs \
		zlib \
		libzip

ENV APCU_VERSION=5.1.18 \
    XDEBUG_VERSION=2.9.4 \
    APP_ENV=prodnamed

RUN set -eux \
	&& apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		icu-dev \
		zlib-dev \
		libzip-dev \
	&& docker-php-ext-install \
		intl \
		zip \
		sockets \
		bcmath \
		pdo \
		pdo_pgsql \
	&& pecl install \
		apcu-${APCU_VERSION} \
		xdebug-${XDEBUG_VERSION} \
	&& docker-php-ext-enable --ini-name 20-apcu.ini apcu \
	&& docker-php-ext-enable --ini-name 05-opcache.ini opcache \
	&& apk del .build-deps

COPY docker/app/conf.d/symfony.ini $PHP_INI_DIR/conf.d/symfony.ini
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini \
    # Increase process-timeout for "composer test" which downloads phpunit
    && composer config --global process-timeout 2000 \
    && adduser -D -u 1000 app \
    && printf "\nuser = app\ngroup = app\n" >> /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /srv/app

# Warm up composer cache for faster builds
#COPY docker/caching/composer.* ./
#RUN COMPOSER_MEMORY_LIMIT=-1 composer install --prefer-dist --no-dev --no-progress --no-suggest --classmap-authoritative --no-interaction --no-scripts \
#    && rm -rf vendor composer.*
# End warm up

COPY . .

RUN mkdir -p var/cache var/logs var/sessions \
    &&  COMPOSER_MEMORY_LIMIT=-1 composer install --prefer-dist --no-dev --no-progress --no-suggest --classmap-authoritative --no-interaction \
    && composer clear-cache \
    && chown -R app: .

ENTRYPOINT ["/srv/app/docker/fpm-entrypoint.sh"]

CMD ["php-fpm"]

FROM nginx:1.15.11-alpine AS {{service-name}}-nginx

COPY docker/nginx/conf.d /etc/nginx/conf.d/
COPY --from={{service-name}}-php /srv/app/public /srv/app/public

EXPOSE 80