#!/usr/bin/env bash
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'

log()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1" >&2; }
info() { echo -e "${CYAN}[i]${NC} $1"; }

echo -e "${CYAN}============================================${NC}"
echo -e "${CYAN}  CI4 HMVC Chatbot — Setup Script${NC}"
echo -e "${CYAN}============================================${NC}"
echo ""

# --------------------------------------------------
# Clone repo if not already inside it
# --------------------------------------------------
if [[ ! -f composer.json ]] || ! grep -q '"shantanusk/chatbot"' composer.json 2>/dev/null; then
    # Check if we're inside the cloned repo via git remote
    if git remote get-url origin 2>/dev/null | grep -q "shantanusk/chatbot"; then
        REPO_CLONED=true
    else
        info "This script must be run from inside the chatbot project."
        read -rp "Clone the repo first? (Y/n): " CLONE
        if [[ ! "$CLONE" =~ ^[Nn] ]]; then
            command -v git >/dev/null 2>&1 || { err "Git is required to clone."; exit 1; }
            git clone git@github.com:shantanusk/chatbot.git chatbot
            cd chatbot
        else
            err "Please clone the repo first: git clone git@github.com:shantanusk/chatbot.git && cd chatbot"
            exit 1
        fi
    fi
fi

PROJECT_DIR="$(pwd)"

# --------------------------------------------------
# Prerequisites
# --------------------------------------------------
info "Checking prerequisites..."

command -v php >/dev/null 2>&1 || { err "PHP is required. See https://php.net"; exit 1; }
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
if [[ "$(printf '%s\n' "8.2" "$PHP_VER" | sort -V | head -n1)" != "8.2" ]]; then
    err "PHP 8.2+ required (found $PHP_VER)"; exit 1
fi
log "PHP $PHP_VER"

command -v composer >/dev/null 2>&1 || { err "Composer is required. See https://getcomposer.org"; exit 1; }
log "Composer $(composer --version 2>&1 | grep -oP '\d+\.\d+\.\d+' | head -1)"

MYSQL_CMD=""
for cmd in mysql mysqld; do
    command -v "$cmd" >/dev/null 2>&1 && { MYSQL_CMD="$cmd"; break; }
done
if [[ -z "$MYSQL_CMD" ]]; then
    warn "MySQL client not found. You'll need to create the database manually."
    HAS_MYSQL=false
else
    HAS_MYSQL=true
    log "MySQL ($MYSQL_CMD)"
fi

for ext in curl mysqli mbstring json; do
    php -m | grep -qi "$ext" || warn "PHP extension '$ext' is missing"
done
log "PHP extensions OK"

echo ""

# --------------------------------------------------
# .env configuration
# --------------------------------------------------
if [[ -f .env ]]; then
    info ".env already exists — skipping configuration"
else
    info "Creating .env from template..."

    read -rp "Database host [localhost]: " DB_HOST
    DB_HOST=${DB_HOST:-localhost}

    read -rp "Database name [chatbot_db]: " DB_NAME
    DB_NAME=${DB_NAME:-chatbot_db}

    read -rp "Database user [root]: " DB_USER
    DB_USER=${DB_USER:-root}

    read -rsp "Database password []: " DB_PASS
    DB_PASS=${DB_PASS:-}
    echo ""

    read -rp "App base URL [http://localhost:8080]: " BASE_URL
    BASE_URL=${BASE_URL:-http://localhost:8080}

    cp env .env
    sed -i "s|# CI_ENVIRONMENT = production|CI_ENVIRONMENT = development|" .env
    sed -i "s|# app.baseURL = ''|app.baseURL = '${BASE_URL}'|" .env
    sed -i "s|# app.indexPage = ''|app.indexPage = ''|" .env
    sed -i "s|# database.default.hostname = localhost|database.default.hostname = ${DB_HOST}|" .env
    sed -i "s|# database.default.database = ci4|database.default.database = ${DB_NAME}|" .env
    sed -i "s|# database.default.username = root|database.default.username = ${DB_USER}|" .env
    sed -i "s|# database.default.password = root|database.default.password = ${DB_PASS}|" .env
    sed -i "s|# database.default.DBDriver = MySQLi|database.default.DBDriver = MySQLi|" .env
    sed -i "s|# database.default.DBPrefix =|database.default.DBPrefix =|" .env
    sed -i "s|# database.default.port = 3306|database.default.port = 3306|" .env

    log ".env configured"
fi

echo ""

# --------------------------------------------------
# Composer install
# --------------------------------------------------
info "Installing Composer dependencies..."
composer install --no-interaction --prefer-dist
log "Composer dependencies installed"

echo ""

# --------------------------------------------------
# Database creation
# --------------------------------------------------
if [[ "$HAS_MYSQL" == true ]]; then
    # shellcheck disable=SC2153
    source .env 2>/dev/null || true
    DB_HOST="${database.default.hostname:-localhost}"
    DB_NAME="${database.default.database:-chatbot_db}"
    DB_USER="${database.default.username:-root}"
    DB_PASS="${database.default.password:-}"

    info "Creating database '${DB_NAME}' if it doesn't exist..."
    MYSQL_ARGS=(-h "$DB_HOST" -u "$DB_USER")
    [[ -n "$DB_PASS" ]] && MYSQL_ARGS+=("-p${DB_PASS}")

    mysql "${MYSQL_ARGS[@]}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null && {
        log "Database '${DB_NAME}' ready"
    } || warn "Could not create database — please create it manually: CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
fi

echo ""

# --------------------------------------------------
# Migrations
# --------------------------------------------------
info "Running migrations..."
php spark migrate --all
log "Migrations complete"

echo ""

# --------------------------------------------------
# Seed (optional)
# --------------------------------------------------
read -rp "Seed sample data? (y/N): " SEED
if [[ "$SEED" =~ ^[Yy] ]]; then
    php spark db:seed "Modules\Chatbot\Database\Seeds\ChatbotSeeder"
    log "Sample data seeded"
fi

echo ""

# --------------------------------------------------
# Permissions
# --------------------------------------------------
info "Setting writable directory permissions..."
chmod -R 755 writable/
chmod -R 755 public/
log "Permissions set"

echo ""

# --------------------------------------------------
# Ollama (optional)
# --------------------------------------------------
if ! command -v ollama >/dev/null 2>&1; then
    read -rp "Install Ollama for local AI? (y/N): " INSTALL_OLLAMA
    if [[ "$INSTALL_OLLAMA" =~ ^[Yy] ]]; then
        info "Installing Ollama..."
        curl -fsSL https://ollama.ai/install.sh | sh
        log "Ollama installed"
    fi
fi

if command -v ollama >/dev/null 2>&1; then
    read -rp "Pull a model (e.g. gemma:2b, llama2)? [gemma:2b]: " MODEL
    MODEL=${MODEL:-gemma:2b}
    ollama pull "$MODEL"
    log "Model '${MODEL}' pulled"
fi

echo ""

# --------------------------------------------------
# Done
# --------------------------------------------------
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Setup complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
info "Start the dev server with:"
echo ""
echo -e "   ${CYAN}php spark serve --port=8080${NC}"
echo ""
info "Then open ${CYAN}http://localhost:8080${NC} in your browser."
echo ""
