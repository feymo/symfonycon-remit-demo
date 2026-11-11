#!/bin/sh
set -e

# Only when the container starts the web server (not for `docker compose run php ./setup.sh`).
if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	if [ ! -f composer.json ]; then
		echo '⚠  No Symfony app here yet: run `make install` first.'
	else
		if [ ! -f vendor/autoload_runtime.php ]; then
			composer install --prefer-dist --no-progress --no-interaction
		fi

		mkdir -p var public/uploads/receipts

		# Keep the SQLite schema up to date (no-op when nothing changed).
		if ls migrations/*.php >/dev/null 2>&1; then
			php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing --allow-no-migration
		fi
	fi
fi

exec docker-php-entrypoint "$@"
