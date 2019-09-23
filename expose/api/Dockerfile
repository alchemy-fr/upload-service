FROM php:7.3.4-fpm-alpine AS expose_api_php

RUN apk add --no-cache --virtual .persistent-deps \
		git \
		postgresql-dev \
		icu-libs \
		zlib \
		libzip

ENV APCU_VERSION 5.1.12
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
	&& docker-php-ext-enable --ini-name 20-apcu.ini apcu \
	&& docker-php-ext-enable --ini-name 05-opcache.ini opcache \
	&& apk del .build-deps

RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY docker $PHP_INI_DIR/conf.d/symfony.ini
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN adduser -D -u 1000 app \
    && printf "\nuser = app\ngroup = app\n" >> /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /srv/app

COPY . .

ENV APP_ENV=prod

RUN mkdir -p var/cache var/logs var/sessions \
    && composer install --prefer-dist --no-dev --no-progress --no-suggest --classmap-authoritative --no-interaction \
    && composer clear-cache \
    && chown -R app: .

CMD ["php-fpm"]

FROM nginx:1.15.11-alpine AS expose_api_nginx

COPY docker/nginx/conf.d /etc/nginx/conf.d/
COPY --from=expose_api_php /srv/app/public /srv/app/public

EXPOSE 80

FROM expose_api_php as expose_worker

RUN apk add --no-cache \
        supervisor \
    && mkdir -p /var/log/supervisor \
    && chown -R app: /var/log/supervisor

COPY docker /etc/supervisor.d/

ENTRYPOINT []

CMD ["/usr/bin/supervisord", "-n"]