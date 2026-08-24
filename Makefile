.PHONY: init up down restart fresh dev logs test

## Interactive first-time setup: env, deps, containers, migrate+seed, dev server.
## Non-interactive: make init SEED_DEMO=y (or n)
init:
	@bash scripts/init.sh

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
