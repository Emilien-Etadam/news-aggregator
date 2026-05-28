#!/usr/bin/env bash
#
# Verify bare-metal install matches current app requirements (reader page, thumbnails, etc.).
# Run as the app user from the project directory, or set PROJECT_DIR.
#
set -euo pipefail

PROJECT_DIR="${PROJECT_DIR:-$HOME/news-aggregator}"

log() { printf '\033[1;36m[%s]\033[0m %s\n' "$(date +%H:%M:%S)" "$*"; }
err() { printf '\033[1;31m[ERR]\033[0m %s\n' "$*" >&2; exit 1; }

[ -d "$PROJECT_DIR" ] || err "Project directory not found: ${PROJECT_DIR}"
cd "$PROJECT_DIR"

export PATH="$HOME/.bun/bin:$PATH"

log "Checking Symfony console"
php bin/console about >/dev/null 2>&1 || err "Symfony console failed — run: composer install"

log "Checking JS bundles"
for js_file in reading-mode.js mark-as-read.js; do
  [ -f "assets/js/${js_file}" ] || err "Missing assets/js/${js_file} — run: bun build assets/ts/*.ts --outdir=assets/js/ --root=assets/ts"
done

log "Checking reader route"
php bin/console debug:router app_article_show --no-ansi >/dev/null 2>&1 \
  || err "Route app_article_show missing — git pull and clear cache"

log "Checking image_url column"
php bin/console dbal:run-sql "SELECT column_name FROM information_schema.columns WHERE table_name = 'article' AND column_name = 'image_url'" --no-ansi 2>/dev/null \
  | grep -q image_url \
  || err "Column article.image_url missing — run: php bin/console doctrine:migrations:migrate --no-interaction"

log "Checking backfill command"
php bin/console list app:articles 2>/dev/null | grep -q backfill-images \
  || err "Command app:articles:backfill-images missing"

if ss -tln 2>/dev/null | grep -q ':8000 '; then
  http_code=$(curl -fsS -o /dev/null -w '%{http_code}' http://127.0.0.1:8000/login || echo "000")
  log "HTTP /login status: ${http_code}"
fi

log "All checks passed."
