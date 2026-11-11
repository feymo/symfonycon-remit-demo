# Remit — SymfonyCon demo app. `make` lists every command.
#
# One project, two parts — this Makefile follows the same split:
#   PART 1 · SYMFONY APP    everything except native/ — the web app you keep evolving (UX Native lives here)
#   PART 2 · NATIVE SHELLS  native/ — the iOS and Android shells (Hotwire Native), written once
#
# First time:            make init        (build, install, local domain, trusted HTTPS — asks for your password twice)
# Every day:             make up / make down
# Before going on stage: make stage
# Phone apps:            make ios / make android

DOMAIN      ?= remit.test
HOSTS_FILE  ?= /etc/hosts
HOSTS_TAG    = \# remit-local
SUDO        ?= sudo
CA_FILE      = var/caddy-root.crt

# Native shells
NATIVE_DIR    = native
SERVER        ?= http://localhost
REMIT_TEAM_ID ?=

DOCKER_COMP  = SERVER_NAME=$(DOMAIN) docker compose
PHP_CONT     = $(DOCKER_COMP) exec php
PHP          = $(PHP_CONT) php
COMPOSER     = $(PHP_CONT) composer
SYMFONY      = $(PHP) bin/console

.DEFAULT_GOAL = help
.PHONY: native-check native-sync-config ios android android-build android-install flush-dns help init build rebuild up down restart logs ps sh install vendor composer sf cc db-reset migration migrate fixtures importmap stage hosts unhosts trust untrust trust-simulator urls open xdebug

## —— Remit 🧾 ———————————————————————————————————————————————————————————————
help: ## This list
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-18s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

init: build install up hosts trust urls ## First run: image, Symfony app, demo data, local domain and trusted HTTPS

# ══════════════════════════════════════════════════════════════════════════════
#  PART 1 · SYMFONY APP — everything except native/
# ══════════════════════════════════════════════════════════════════════════════

## ══ PART 1 · SYMFONY APP (everything except native/) ═════════════════════════
## —— Docker 🐳 ——————————————————————————————————————————————————————————————
build: ## Build the FrankenPHP image (pulls the latest 1.x / PHP 8.5 base)
	@$(DOCKER_COMP) build --pull

rebuild: ## Build the image from scratch, no cache
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the app in the background
	@$(DOCKER_COMP) up --detach --wait
	@echo "→ https://$(DOMAIN)"

down: ## Stop the app
	@$(DOCKER_COMP) down --remove-orphans

restart: down up ## Stop then start

logs: ## Follow the server logs
	@$(DOCKER_COMP) logs --tail=100 --follow

ps: ## Container status
	@$(DOCKER_COMP) ps

sh: ## Shell inside the container
	@$(PHP_CONT) bash

xdebug: ## Restart with Xdebug on (step debugging, trigger mode)
	@XDEBUG_MODE=debug $(DOCKER_COMP) up --detach --wait --force-recreate

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
install: ## Create the Symfony app around our files, fonts, database, demo data (runs setup.sh in a container)
	@$(DOCKER_COMP) run --rm --no-deps php ./setup.sh

vendor: ## composer install
	@$(COMPOSER) install --prefer-dist --no-progress --no-interaction

composer: ## Run Composer, e.g. make composer c='require symfony/foo'
	@$(COMPOSER) $(c)

sf: ## Run bin/console, e.g. make sf c=debug:router
	@$(SYMFONY) $(c)

cc: ## Clear the cache
	@$(SYMFONY) cache:clear

importmap: ## Download the JavaScript packages listed in importmap.php
	@$(SYMFONY) importmap:install

## —— Database 🗄️ ————————————————————————————————————————————————————————————
# SQLite + DBAL 4: database:drop just deletes var/data.db (never add --if-exists), and there is
# no database:create — the file reappears on the first connection (the migrations).
db-reset: ## Back to the demo state: fresh SQLite database, demo data, receipt photos
	@$(SYMFONY) doctrine:database:drop --force
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction
	@$(PHP_CONT) sh -c 'find public/uploads/receipts -type f ! -name .gitkeep -delete'
	@$(SYMFONY) doctrine:fixtures:load --no-interaction

migration: ## Generate a migration from the entities
	@$(SYMFONY) make:migration

migrate: ## Apply pending migrations
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction

fixtures: ## Reload the demo data (keeps the schema)
	@$(PHP_CONT) sh -c 'find public/uploads/receipts -type f ! -name .gitkeep -delete'
	@$(SYMFONY) doctrine:fixtures:load --no-interaction

stage: up db-reset cc ## Right before the talk: demo data reset, warm cache, URLs to type
	@$(SYMFONY) cache:warmup >/dev/null
	@$(MAKE) --no-print-directory urls

## —— Local domain & HTTPS 🌐 ———————————————————————————————————————————————
hosts: ## Point the local domain (remit.test) to this Mac in /etc/hosts (sudo)
	@if grep -q "[[:space:]]$(DOMAIN) $(HOSTS_TAG)$$" $(HOSTS_FILE); then \
		echo "$(DOMAIN) is already in $(HOSTS_FILE)"; \
	else \
		printf '127.0.0.1 $(DOMAIN) $(HOSTS_TAG)\n::1 $(DOMAIN) $(HOSTS_TAG)\n' | $(SUDO) tee -a $(HOSTS_FILE) >/dev/null \
			&& echo "Added $(DOMAIN) to $(HOSTS_FILE)"; \
	fi
	@$(MAKE) --no-print-directory flush-dns

unhosts: ## Remove the local domain from /etc/hosts (sudo)
	@mkdir -p var
	@if grep -q "$(HOSTS_TAG)$$" $(HOSTS_FILE); then \
		grep -v "$(HOSTS_TAG)$$" $(HOSTS_FILE) > var/hosts.tmp \
			&& $(SUDO) cp var/hosts.tmp $(HOSTS_FILE) && rm -f var/hosts.tmp \
			&& echo "Removed the remit-local entries from $(HOSTS_FILE)"; \
	else \
		echo "Nothing to remove"; \
	fi
	@$(MAKE) --no-print-directory flush-dns

flush-dns:
	@if [ "$$(uname)" = Darwin ] && [ "$(HOSTS_FILE)" = /etc/hosts ]; then \
		$(SUDO) dscacheutil -flushcache; $(SUDO) killall -HUP mDNSResponder 2>/dev/null || true; \
	fi

trust: ## Trust the local HTTPS certificate authority in the macOS keychain (sudo)
	@mkdir -p var
	@$(DOCKER_COMP) cp php:/data/caddy/pki/authorities/local/root.crt $(CA_FILE)
	@$(SUDO) security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain $(CA_FILE)
	@echo "https://$(DOMAIN) is now trusted (restart Firefox, which uses its own store)"

untrust: ## Remove the local certificate authority from the keychain (sudo)
	@$(SUDO) security remove-trusted-cert -d $(CA_FILE)

trust-simulator: ## Trust the local CA in the booted iOS Simulator
	@mkdir -p var
	@$(DOCKER_COMP) cp php:/data/caddy/pki/authorities/local/root.crt $(CA_FILE)
	@xcrun simctl keychain booted add-root-cert $(CA_FILE)
	@echo "The booted simulator now trusts https://$(DOMAIN)"

## —— URLs for the phones 📱 ———————————————————————————————————————————————————————————
urls: ## Which URL to use where (browser, iOS Simulator, iPhone, Android emulator)
	@LAN_IP=$$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null || hostname -I 2>/dev/null | cut -d' ' -f1); \
	LOCAL_NAME=$$(scutil --get LocalHostName 2>/dev/null || hostname -s); \
	printf '\n'; \
	printf '  \033[1mBrowser on this Mac\033[0m     https://$(DOMAIN)\n'; \
	printf '  \033[1miOS Simulator\033[0m           https://$(DOMAIN)   (after make trust-simulator)\n'; \
	printf '  \033[1miPhone (real device)\033[0m    http://%s.local   or   http://%s\n' "$$LOCAL_NAME" "$$LAN_IP"; \
	printf '  \033[1mAndroid emulator\033[0m        http://10.0.2.2\n'; \
	printf '\n  Path configuration: <base URL>/config/ios_v1.json and /config/android_v1.json\n\n'

open: ## Open the app in the browser
	@open https://$(DOMAIN) 2>/dev/null || xdg-open https://$(DOMAIN)

# ══════════════════════════════════════════════════════════════════════════════
#  PART 2 · NATIVE SHELLS — native/ (Hotwire Native, written once)
#  They load the Symfony app over HTTP: start it first (make up).
# ══════════════════════════════════════════════════════════════════════════════

## ══ PART 2 · NATIVE SHELLS (native/ — Hotwire Native) ════════════════════════
## —— Shared 🔗 ——————————————————————————————————————————————————————————————
native-check: ## Is the Symfony app answering the shells? (path configuration served by UX Native)
	@curl -fsS -o /dev/null $(SERVER)/config/ios_v1.json \
		&& echo "✓ $(SERVER) answers" \
		|| { echo "✗ $(SERVER) does not answer: run make up"; exit 1; }

native-sync-config: native-check ## Copy the path rules from AppNativeConfiguration into both shells (bundled for the first launch)
	@curl -fsS $(SERVER)/config/ios_v1.json -o $(NATIVE_DIR)/ios/Remit/path-configuration.json
	@curl -fsS $(SERVER)/config/android_v1.json -o $(NATIVE_DIR)/android/app/src/main/assets/json/path-configuration.json
	@echo "✓ Bundled path configuration updated from AppNativeConfiguration"

## —— iOS 🍏 ————————————————————————————————————————————————————————————————
ios: ## Generate and open the Xcode project (Xcode + brew install xcodegen; REMIT_TEAM_ID=… for the iPhone)
	@REMIT_TEAM_ID=$(REMIT_TEAM_ID) ./$(NATIVE_DIR)/ios/generate.sh

## —— Android 🤖 ————————————————————————————————————————————————————————————
android: ## Open the project in Android Studio
	@open -a "Android Studio" $(NATIVE_DIR)/android

android-build: ## Build the debug APK
	@cd $(NATIVE_DIR)/android && ./gradlew assembleDebug

android-install: ## Install and launch on the running emulator
	@cd $(NATIVE_DIR)/android && ./gradlew installDebug
	@adb shell am start -n com.bludevon.remit/.MainActivity
