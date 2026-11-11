#!/usr/bin/env bash
#
# Turns this folder into a runnable Symfony 8 app (Remit, the SymfonyCon demo).
#
#   With Docker (recommended):  make install      — runs this script inside the FrankenPHP container
#   Without Docker:             ./setup.sh        — needs PHP 8.4 (intl, pdo_sqlite) and Composer on the Mac
#
# What it does:
#   1. builds a Symfony 8 "webapp" skeleton in a temp folder (+ UX Native, Twig Intl, fixtures),
#      then copies it here WITHOUT touching the files already in this folder;
#   2. SQLite database in var/data.db — everything runs locally, no DB server;
#   3. downloads IBM Plex Sans into assets/fonts/ (so the stage never needs the venue Wi-Fi);
#   4. creates the schema and loads the demo data (Warsaw trip + 6 past months).
#
# Safe to run again: steps already done are skipped, fixtures are reloaded.
set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$APP_DIR"

step() { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }
fail() { printf '\033[31m✗ %s\033[0m\n' "$1" >&2; exit 1; }

step "Checking tools"
for tool in php composer curl; do
    command -v "$tool" >/dev/null 2>&1 || fail "$tool is not installed"
done
php -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' || fail "PHP 8.4 or newer is required (found $(php -r 'echo PHP_VERSION;'))"
php -m | grep -qi '^pdo_sqlite$' || fail "the pdo_sqlite PHP extension is required"
php -m | grep -qi '^intl$' || fail "the intl PHP extension is required (amounts are formatted with format_currency)"

if [ -f composer.json ]; then
    step "Symfony skeleton already here — skipped"
else
    step "Creating a Symfony 8 webapp skeleton (temp folder)"
    TMP_DIR="$(mktemp -d)"
    trap 'rm -rf "$TMP_DIR"' EXIT
    composer create-project symfony/skeleton:"^8.0" "$TMP_DIR/app" --no-interaction
    (
        cd "$TMP_DIR/app"
        # Our own compose.yaml / Dockerfile run the app: no Docker files from the recipes (Postgres, Mailpit…)
        composer config --json extra.symfony.docker false
        composer require webapp --no-interaction
        composer require symfony/ux-native twig/intl-extra --no-interaction
        composer require --dev doctrine/doctrine-fixtures-bundle --no-interaction
    )

    step "Merging the skeleton into this folder (existing files are kept)"
    SKEL="$TMP_DIR/app"
    # Ours win over the recipes' examples: base.html.twig, app.css, AppFixtures, native configuration.
    rm -rf "$SKEL/src/DataFixtures" "$SKEL/src/Native" "$SKEL/var" "$SKEL"/compose*.yaml "$SKEL"/docker-compose*.yml
    [ -e vendor ] || mv "$SKEL/vendor" "$APP_DIR/vendor"
    (cd "$SKEL" && find . \( -type f -o -type l \) -print) | while IFS= read -r file; do
        if [ ! -e "$APP_DIR/$file" ] && [ ! -L "$APP_DIR/$file" ]; then
            mkdir -p "$APP_DIR/$(dirname "$file")"
            cp -pP "$SKEL/$file" "$APP_DIR/$file"
        fi
    done
fi

step "Local configuration (SQLite, uploads)"
touch .env.local
grep -q '^DATABASE_URL=' .env.local || echo 'DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"' >> .env.local
mkdir -p public/uploads/receipts assets/fonts var
grep -qs '^/public/uploads/' .gitignore || printf '\n###> remit ###\n/public/uploads/*\n!/public/uploads/receipts/.gitkeep\n###< remit ###\n' >> .gitignore

step "Fonts (IBM Plex Sans, SIL Open Font License)"
for weight in 400 500 600; do
    for subset in latin latin-ext; do
        font="ibm-plex-sans-$subset-$weight-normal.woff2"
        if [ ! -s "assets/fonts/$font" ]; then
            curl -fsSL "https://cdn.jsdelivr.net/npm/@fontsource/ibm-plex-sans@5/files/$font" -o "assets/fonts/$font" \
                || { rm -f "assets/fonts/$font"; echo "  ! $font not downloaded — AssetMapper will refuse to render pages until it is in assets/fonts/"; }
        fi
    done
done

step "Database"
# SQLite: nothing to create — var/data.db appears on the first connection.
# (DBAL 4 has no CREATE DATABASE for SQLite: doctrine:database:create always fails on it.)
if ! ls migrations/*.php >/dev/null 2>&1; then
    php bin/console make:migration --no-interaction
fi
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

step "Demo data"
find public/uploads/receipts -type f ! -name .gitkeep -delete   # photos from a previous run
php bin/console doctrine:fixtures:load --no-interaction

printf '\n\033[32m✓ Ready.\033[0m\n'
if [ -f /.dockerenv ]; then
    cat <<'TXT'

  Start it:            make up
  Where to open it:    make urls      (https://remit.test once `make hosts` and `make trust` are done)

TXT
else
    cat <<'TXT'

  Start the server (reachable from the iPhone and the Android emulator on the same network):
      symfony serve --allow-all-ip

  Then open http://127.0.0.1:8000 — the app opens on November 2026.
  Native shells: point Hotwire Native to http://<your Mac's IP>:8000/expenses

TXT
fi
