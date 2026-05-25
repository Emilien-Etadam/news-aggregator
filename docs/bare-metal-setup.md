# Bare-metal Setup (Debian 13)

Install and run the news-aggregator on Debian 13 without Docker. For the Docker workflow, see [CONTRIBUTING.md](../CONTRIBUTING.md).

## Prerequisites

| Component | Version / notes |
|-----------|-----------------|
| PHP | 8.4 (see [PHP version check](#php-version-check)) |
| PostgreSQL | 17 + pgvector |
| Bun | latest (TypeScript compilation) |
| Composer | 2.x |
| Symfony CLI | optional (dev only) |
| FrankenPHP | latest |

### PHP extensions

FrankenPHP embeds its own PHP SAPI — **php-fpm is not required**.

Install via APT (see below): `ctype`, `iconv`, `intl`, `opcache`, `pdo_pgsql`, `pdo_sqlite`, `zip`, `apcu`, `mbstring`, `xml`, `curl`.

`composer.json` requires `ext-ctype` and `ext-iconv`; Loupe/SEAL search needs `pdo_sqlite`.

## Native APT packages

Debian 13 (trixie) ships PHP 8.4 and PostgreSQL 17 natively.

```bash
sudo apt update
sudo apt install -y \
  git curl unzip \
  postgresql-17 postgresql-17-pgvector \
  php8.4-cli php8.4-common php8.4-intl php8.4-opcache \
  php8.4-pgsql php8.4-sqlite3 php8.4-zip php8.4-apcu \
  php8.4-mbstring php8.4-xml php8.4-curl
```

## PHP version check

`composer.json` requires `php >=8.4.19`. Debian security packages may ship 8.4.16 — verify before `composer install`:

```bash
php -v
```

If the version is below 8.4.19, add the [Sury PHP repository](https://packages.sury.org/php/) (optional, not the default path):

```bash
sudo apt install -y lsb-release ca-certificates curl
sudo curl -fsSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/php-sury.gpg
echo "deb [signed-by=/usr/share/keyrings/php-sury.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
  | sudo tee /etc/apt/sources.list.d/php-sury.list
sudo apt update
sudo apt install -y php8.4-cli php8.4-common php8.4-intl php8.4-opcache \
  php8.4-pgsql php8.4-sqlite3 php8.4-zip php8.4-apcu \
  php8.4-mbstring php8.4-xml php8.4-curl
```

Re-check with `php -v` (expect 8.4.21+ from Sury).

## Manual installations

### Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### FrankenPHP

Follow the [official install guide](https://frankenphp.dev/docs/install/). Typical path: `/usr/local/bin/frankenphp`. Adjust unit files if yours differs.

### Bun

```bash
curl -fsSL https://bun.sh/install | bash
```

Ensure `bun` is on `PATH` for your shell.

### Symfony CLI (optional)

```bash
curl -sS https://get.symfony.com/cli/installer | bash
```

Only needed for option C in [Running the application](#running-the-application). Not required for production-like bare-metal setups.

## Dedicated user

Create a non-root user for running the app and systemd user services:

```bash
sudo adduser --disabled-password --gecos "" app
sudo loginctl enable-linger app
```

Clone and operate the project as this user (`%h` in unit files = `/home/app`).

## PostgreSQL

Create the application role and databases (aligned with project defaults):

```bash
sudo -u postgres psql <<'SQL'
CREATE USER app WITH PASSWORD 'CHANGE_ME';
CREATE DATABASE app OWNER app;
CREATE DATABASE app_test OWNER app;
GRANT ALL PRIVILEGES ON DATABASE app TO app;
GRANT ALL PRIVILEGES ON DATABASE app_test TO app;
\c app
CREATE EXTENSION IF NOT EXISTS vector;
\c app_test
CREATE EXTENSION IF NOT EXISTS vector;
GRANT ALL ON SCHEMA public TO app;
SQL
```

Doctrine appends `_test` automatically in `APP_ENV=test` (`config/packages/doctrine.php`), so `app` becomes `app_test` — the explicit `app_test` database matches integration tests.

## Project bootstrap

```bash
git clone https://github.com/YOUR_FORK/news-aggregator.git
cd news-aggregator
composer install
cp .env.example .env.local
```

### Secrets and `.env.local`

Generate values (do not commit real secrets):

```bash
# Symfony secret
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Mercure JWT (use the same value for all three Symfony/Caddy keys below)
openssl rand -hex 32
```

Edit `.env.local`:

```dotenv
APP_SECRET=<generated-secret>

DATABASE_URL="postgresql://app:CHANGE_ME@127.0.0.1:5432/app?serverVersion=17&charset=utf8"

ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=CHANGE_ME

# Bare-metal paths (required for Caddyfile.example)
APP_ROOT=/home/app/news-aggregator
SERVER_NAME=localhost

# Mercure — Symfony publisher
MERCURE_URL=http://127.0.0.1/.well-known/mercure
MERCURE_PUBLIC_URL=http://127.0.0.1/.well-known/mercure
MERCURE_JWT_SECRET=<generated-hex>

# Mercure — Caddy hub (same secret as MERCURE_JWT_SECRET)
MERCURE_PUBLISHER_JWT_KEY=<generated-hex>
MERCURE_SUBSCRIBER_JWT_KEY=<generated-hex>
```

#### Admin password

- **Use `ADMIN_PASSWORD`** (plaintext). `SeedDataCommand` hashes it at runtime via `UserPasswordHasherInterface` (`config/services.php`).
- **`ADMIN_PASSWORD_HASH` in `.env.example` is unused** — auth loads the user from the database, not from env. Do not set it expecting login to work.
- If login fails after seeding, see [Troubleshooting](#troubleshooting).

#### Mercure

| Variable | Consumer |
|----------|----------|
| `MERCURE_URL` | Symfony (server-side publish) |
| `MERCURE_PUBLIC_URL` | Browser SSE (`EventSource`) |
| `MERCURE_JWT_SECRET` | Symfony Mercure bundle |
| `MERCURE_PUBLISHER_JWT_KEY` | Caddy Mercure hub |
| `MERCURE_SUBSCRIBER_JWT_KEY` | Caddy Mercure hub |

Generate one secret with `openssl rand -hex 32` and reuse it for all five Mercure-related values unless you have a reason to split them.

Adjust `MERCURE_PUBLIC_URL` to match how you reach the host (hostname, port, TLS).

For ports below 1024, either run FrankenPHP as root (not recommended) or bind to a high port:

```dotenv
SERVER_NAME=:8080
MERCURE_URL=http://127.0.0.1:8080/.well-known/mercure
MERCURE_PUBLIC_URL=http://127.0.0.1:8080/.well-known/mercure
```

### Database migrations and seed

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-data
php bin/console app:search-reindex
```

`app:seed-data` creates categories, sources, the admin user, and digest configs. It **skips the admin user if one already exists** (`UserRepository::findFirst()` in `SeedDataCommand`) — it does not update the password.

**Reset admin credentials:**

```bash
php bin/console dbal:run-sql 'DELETE FROM "user"'
php bin/console app:seed-data
```

Ensure `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env.local` match what you expect before re-seeding.

### TypeScript

Assets are plain TypeScript scripts (no ES modules). Running `tsc` on the whole tree fails with global scope collisions (`TS2451`, `TS2393`) — e.g. duplicate `DEBOUNCE_MS`, `STORAGE_KEY`, `init()`.

Compile per file with Bun (same as the Docker `Makefile` target):

```bash
bun build assets/ts/*.ts --outdir=assets/js/ --root=assets/ts
```

Watch mode:

```bash
bun build assets/ts/*.ts --outdir=assets/js/ --root=assets/ts --watch
```

Re-run after editing any `assets/ts/*.ts` file.

## Running the application

Five Messenger transports are defined in `config/packages/messenger.php`:

| Transport | Consumer |
|-----------|----------|
| `async` | `news-worker-async` |
| `async_enrich` | `news-worker-enrich` |
| `async_fulltext` | `news-worker-fulltext` |
| `scheduler_fetch` | `news-scheduler` |
| `scheduler_maintenance` | `news-scheduler` |

`scheduler_*` transports are auto-registered by `#[AsSchedule('fetch')]` and `#[AsSchedule('maintenance')]`.

### Option A — manual (tmux)

```bash
# Terminal 1 — web
frankenphp run --config docs/bare-metal/Caddyfile.example

# Terminal 2–5 — workers
php bin/console messenger:consume async --time-limit=3600
php bin/console messenger:consume async_enrich --time-limit=3600
php bin/console messenger:consume async_fulltext --time-limit=3600
php bin/console messenger:consume scheduler_fetch scheduler_maintenance --time-limit=3600
```

Set `WorkingDirectory` to the project root and load `.env.local` (Symfony reads it automatically; FrankenPHP/Caddy needs Mercure vars in the environment — `export $(grep -v '^#' .env.local | xargs)` or use direnv).

### Option B — systemd user services (recommended)

Install unit files (adjust `php` / `frankenphp` paths if needed):

```bash
mkdir -p ~/.config/systemd/user
cp docs/bare-metal/systemd/news-*.service ~/.config/systemd/user/
systemctl --user daemon-reload
systemctl --user enable --now news-web news-worker-async news-worker-enrich news-worker-fulltext news-scheduler
```

Check status:

```bash
systemctl --user status news-web
journalctl --user -u news-web -f
```

The web unit loads `EnvironmentFile=%h/news-aggregator/.env.local` for Mercure and `APP_ROOT`.

If `systemctl --user` fails with a missing runtime dir:

```bash
export XDG_RUNTIME_DIR=/run/user/$(id -u)
loginctl enable-linger "$USER"
```

### Option C — Symfony CLI (not recommended)

```bash
symfony server:start
```

Observed issue: `--listen-ip` does not bind as expected in some environments. Prefer option B for a stable local setup.

## Troubleshooting

| Symptom | Cause / fix |
|---------|-------------|
| `TS2451` / `TS2393` on `tsc` | Global const/function collisions across `assets/ts/*.ts`. Use Bun per-file build (see above). |
| Invalid credentials after seed | (1) Set `ADMIN_PASSWORD`, not `ADMIN_PASSWORD_HASH`. (2) User already existed — seed skipped password update. Run `DELETE FROM "user"` then re-seed. (3) `ADMIN_EMAIL` mismatch. |
| `systemctl --user` fails | Set `XDG_RUNTIME_DIR=/run/user/$(id -u)`; enable lingering with `loginctl enable-linger`. |
| Symfony server bind errors | Use FrankenPHP + systemd (option B) instead of `symfony server:start`. |
| Mercure/SSE not working | Verify all Mercure env vars; web must use `frankenphp run` + `Caddyfile.example`, not `frankenphp php-server`. |
| Search returns nothing | Run `php bin/console app:search-reindex`. |

## Development workflow

Restart a single worker after code changes:

```bash
systemctl --user restart news-worker-enrich
```

Clear cache:

```bash
php bin/console cache:clear
```

Tail logs:

```bash
journalctl --user -u news-worker-async -f
```

AI features work without `OPENROUTER_API_KEY` — services fall back to rule-based categorization, summarization, and keyword extraction (`config/services.php`).

## Production hardening

Out of scope for this document. Use TLS termination, firewall rules, secret rotation, and non-default credentials before exposing the instance to a network.
