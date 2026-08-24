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
	read -rp "Seed 30 sample orders per company (with activity logs) on top of the base data? [y/N]: " SEED_DEMO || true
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
set_env SCOUT_DRIVER meilisearch
set_env MEILISEARCH_HOST http://meilisearch:7700
set_env MEILISEARCH_KEY masterKey

echo ""
echo "Starting containers (Sail: app, MySQL, Meilisearch)..."
# --force-recreate ensures the meilisearch container picks up MEILISEARCH_KEY from
# .env even if a container from a previous run (with a different/blank key) is still around.
vendor/bin/sail up -d --force-recreate

if ! grep -q "^APP_KEY=base64" .env; then
	echo "Generating app key..."
	vendor/bin/sail artisan key:generate --ansi
fi

echo "Installing frontend dependencies..."
vendor/bin/sail npm install

echo "Running migrations and seeding base data (roles, permissions, default users)..."
vendor/bin/sail artisan migrate:fresh --seed

echo "Seeding companies, branches/sites, and service providers..."
vendor/bin/sail artisan db:seed --class=ServiceProviderSeeder
vendor/bin/sail artisan db:seed --class=CompanySeeder

if [[ "$SEED_DEMO" =~ ^[Yy] ]]; then
	echo "Seeding demo orders (30 per company) with activity logs..."
	vendor/bin/sail artisan db:seed --class=MonthlyReportDataSeeder
	vendor/bin/sail artisan db:seed --class=DemoOrdersSeeder
fi

echo "Indexing search (Meilisearch)..."
for model in Grade Order User Company ContainerOption Branch Site ServiceProvider WasteStream; do
	vendor/bin/sail artisan scout:import "App\\Models\\${model}"
done

echo "Building frontend assets..."
vendor/bin/sail npm run build

echo ""
echo "Done. App running at http://localhost:$APP_PORT_INPUT"
echo "Default login: admin@wasteflow.example.com / password"
echo ""
echo "Containers are running in the background ('make down' to stop them)."
echo "For hot-reloading frontend changes, run 'make dev' in another terminal."
