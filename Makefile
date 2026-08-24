SHELL := /bin/bash
.ONESHELL:
.SHELLFLAGS := -eu -o pipefail -c
.PHONY: init up down restart fresh dev stop-dev logs test

COMPOSER_BOOTSTRAP := docker run --rm \
	-u "$$(id -u):$$(id -g)" \
	-v "$$(pwd)":/var/www/html \
	-w /var/www/html \
	laravelsail/php84-composer:latest \
	composer install --ignore-platform-reqs

## Interactive first-time setup: env, deps, containers, migrate+seed, dev server.
init:
	@if ! docker info >/dev/null 2>&1; then \
		echo "Docker doesn't seem to be running. Start Docker Desktop (or the Docker daemon) and try again."; \
		exit 1; \
	fi

	if [ ! -f .env ]; then cp .env.example .env; fi

	if [ ! -d vendor ]; then \
		echo "Installing PHP dependencies (via a throwaway container, no local PHP needed)..."; \
		$(COMPOSER_BOOTSTRAP); \
	fi

	echo ""
	echo "A few questions to set this up:"
	read -rp "App name [WasteFlow]: " APP_NAME_INPUT
	APP_NAME_INPUT=$${APP_NAME_INPUT:-WasteFlow}

	read -rp "Local port to serve the app on [80]: " APP_PORT_INPUT
	APP_PORT_INPUT=$${APP_PORT_INPUT:-80}

	read -rp "Seed extra demo data (sample service providers) on top of the base roles/users? [y/N]: " SEED_DEMO
	SEED_DEMO=$${SEED_DEMO:-n}

	sed "s|^APP_NAME=.*|APP_NAME=\"$$APP_NAME_INPUT\"|" .env > .env.tmp && mv .env.tmp .env
	if grep -q "^APP_PORT=" .env; then \
		sed "s|^APP_PORT=.*|APP_PORT=$$APP_PORT_INPUT|" .env > .env.tmp && mv .env.tmp .env; \
	else \
		echo "APP_PORT=$$APP_PORT_INPUT" >> .env; \
	fi
	if ! grep -q "^DB_CONNECTION=mysql" .env; then \
		sed "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env > .env.tmp && mv .env.tmp .env; \
	fi
	for kv in "DB_HOST=mysql" "DB_PORT=3306" "DB_DATABASE=wasteflow" "DB_USERNAME=sail" "DB_PASSWORD=password"; do \
		key=$${kv%%=*}; \
		if grep -q "^$${key}=" .env; then \
			sed "s|^$${key}=.*|$$kv|" .env > .env.tmp && mv .env.tmp .env; \
		else \
			echo "$$kv" >> .env; \
		fi; \
	done

	echo ""
	echo "Starting containers (Sail: app, MySQL, Meilisearch)..."
	vendor/bin/sail up -d

	if ! grep -q "^APP_KEY=base64" .env; then \
		echo "Generating app key..."; \
		vendor/bin/sail artisan key:generate --ansi; \
	fi

	echo "Installing frontend dependencies..."
	vendor/bin/sail npm install

	echo "Running migrations and seeding base data (roles, permissions, default users)..."
	vendor/bin/sail artisan migrate:fresh --seed

	if [[ "$$SEED_DEMO" =~ ^[Yy] ]]; then \
		echo "Seeding demo service providers..."; \
		vendor/bin/sail artisan db:seed --class=ServiceProviderSeeder; \
	fi

	echo ""
	echo "Done. App running at $${APP_URL:-http://localhost}:$$APP_PORT_INPUT"
	echo "Default login: admin@wasteflow.example.com / password"
	echo ""
	echo "Starting the Vite dev server (Ctrl+C to stop; containers keep running — 'make down' to stop those too)..."
	vendor/bin/sail npm run dev

## Start containers without the full init flow (after the first `make init`).
up:
	vendor/bin/sail up -d
	vendor/bin/sail npm run dev

## Stop containers.
down:
	vendor/bin/sail down

## Restart containers.
restart: down up

## Wipe and re-seed the database (base roles/users only).
fresh:
	vendor/bin/sail artisan migrate:fresh --seed

## Run the Vite dev server only (containers must already be up).
dev:
	vendor/bin/sail npm run dev

## Tail container logs.
logs:
	vendor/bin/sail logs -f

## Run the backend test suite.
test:
	vendor/bin/sail artisan test
