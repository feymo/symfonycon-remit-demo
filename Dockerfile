#syntax=docker/dockerfile:1

# Remit — FrankenPHP image (PHP 8.5). Built locally: `make build`.
# Pinned to the FrankenPHP 1.x line and Debian trixie.
FROM dunglas/frankenphp:1-php8.5-trixie AS frankenphp_upstream

# ---------------------------------------------------------------- base
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app

# git + unzip for Composer, file for Symfony's MIME type guesser (receipt uploads)
RUN apt-get update && apt-get install -y --no-install-recommends \
		file \
		git \
		unzip \
	&& rm -rf /var/lib/apt/lists/*

RUN set -eux; \
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		pdo_sqlite \
		zip \
	;

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link docker/frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile" ]

# ---------------------------------------------------------------- dev (also used on stage: the profiler is part of the talk)
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=off

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
	install-php-extensions \
		xdebug \
	;

COPY --link docker/frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

# --watch reloads the Caddyfile when it changes
CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch" ]
