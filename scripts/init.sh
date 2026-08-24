#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

if ! docker info >/dev/null 2>&1; then
	echo "Docker doesn't seem to be running. Start Docker Desktop (or the Docker daemon) and try again."
	exit 1
fi

if [ ! -f .env ]; then
	cp .env.example .env
fi

if [ ! -d vendor ]; then
	echo "Installing PHP dependencies (via a throwaway container, no local PHP needed)..."
	docker run --rm \
		-u "$(id -u):$(id -g)" \
		-v "$(pwd)":/var/www/html \
		-w /var/www/html \
		laravelsail/php84-composer:latest \
		composer install --ignore-platform-reqs
fi

echo ""
echo "A few questions to set this up:"

read -rp "App name [WasteFlow]: " APP_NAME_INPUT || true
APP_NAME_INPUT=${APP_NAME_INPUT:-WasteFlow}

read -rp "Local port to serve the app on [80]: " APP_PORT_INPUT || true
APP_PORT_INPUT=${APP_PORT_INPUT:-80}

if [ -n "${SEED_DEMO:-}" ]; then
	echo "Seed extra demo data? $SEED_DEMO (from 'make init SEED_DEMO=...')"
else
	read -rp "Seed extra demo data (companies/branches/sites, service providers, sample orders) on top of the base roles/users? [y/N]: " SEED_DEMO || true
	SEED_DEMO=${SEED_DEMO:-n}
fi
echo "-> seed extra demo data: $SEED_DEMO"

set_env() {
	local key="$1" value="$2"
	if grep -q "^${key}=" .env; then
		sed "s|^${key}=.*|${key}=${value}|" .env > .env.tmp && mv .env.tmp .env
	else
		echo "${key}=${value}" >> .env
	fi
}

set_env APP_NAME "\"$APP_NAME_INPUT\""
set_env APP_PORT "$APP_PORT_INPUT"
set_env DB_CONNECTION mysql
set_env DB_HOST mysql
set_env DB_PORT 3306
set_env DB_DATABASE wasteflow
set_env DB_USERNAME sail
set_env DB_PASSWORD password

echo ""
echo "Starting containers (Sail: app, MySQL, Meilisearch)..."
vendor/bin/sail up -d

if ! grep -q "^APP_KEY=base64" .env; then
	echo "Generating app key..."
	vendor/bin/sail artisan key:generate --ansi
fi

echo "Installing frontend dependencies..."
vendor/bin/sail npm install

echo "Running migrations and seeding base data (roles, permissions, default users)..."
vendor/bin/sail artisan migrate:fresh --seed

if [[ "$SEED_DEMO" =~ ^[Yy] ]]; then
	echo "Seeding demo data (service providers, companies/branches/sites, orders)..."
	vendor/bin/sail artisan db:seed --class=ServiceProviderSeeder
	vendor/bin/sail artisan db:seed --class=CompanySeeder
	vendor/bin/sail artisan db:seed --class=MonthlyReportDataSeeder
fi

echo "Building frontend assets..."
vendor/bin/sail npm run build

echo ""
echo "Done. App running at http://localhost:$APP_PORT_INPUT"
echo "Default login: admin@wasteflow.example.com / password"
echo ""
echo "Containers are running in the background ('make down' to stop them)."
echo "For hot-reloading frontend changes, run 'make dev' in another terminal."
