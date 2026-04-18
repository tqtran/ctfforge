#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
SECRETS_FILE="${REPO_ROOT}/framework/secrets/db.php"
SCHEMA_FILE="${SCRIPT_DIR}/schema.sql"

DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-ctfforge}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
FORCE=0

if [[ "${1:-}" == "--force" ]]; then
  FORCE=1
elif [[ $# -gt 0 ]]; then
  printf 'Usage: %s [--force]\n' "${BASH_SOURCE[0]}" >&2
  exit 1
fi

php_literal() {
  php -r 'var_export($argv[1]);' "$1"
}

mkdir -p "${REPO_ROOT}/framework/secrets" "${REPO_ROOT}/uploads"

if [[ ! -f "${SECRETS_FILE}" || "${FORCE}" -eq 1 ]]; then
  cat > "${SECRETS_FILE}" <<EOF
<?php
define('DB_HOST', $(php_literal "${DB_HOST}"));
define('DB_NAME', $(php_literal "${DB_NAME}"));
define('DB_USER', $(php_literal "${DB_USER}"));
define('DB_PASS', $(php_literal "${DB_PASS}"));
EOF
  if ! chmod 600 "${SECRETS_FILE}"; then
    printf 'Warning: could not restrict permissions on %s\n' "${SECRETS_FILE}" >&2
  fi
fi

cat <<EOF
Deployment files are ready.

Database config: ${SECRETS_FILE}
Schema file:     ${SCHEMA_FILE}

Next steps:
  1. Create the database if it does not already exist.
  2. Import the schema:
     mysql -h "${DB_HOST}" -u "${DB_USER}" --database="${DB_NAME}" -p < "${SCHEMA_FILE}"
     This command will prompt for the database password.
  3. Point your web server at:
     ${REPO_ROOT}

If you need different database credentials, rerun this command with DB_HOST, DB_NAME, DB_USER, and DB_PASS set in the environment and pass --force to regenerate ${SECRETS_FILE}.
EOF
