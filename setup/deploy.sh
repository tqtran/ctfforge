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

php_escape() {
  printf '%s' "$1" | sed "s/[\\\\']/\\\\&/g"
}

mkdir -p "${REPO_ROOT}/framework/secrets" "${REPO_ROOT}/uploads"

if [[ ! -f "${SECRETS_FILE}" ]]; then
  cat > "${SECRETS_FILE}" <<EOF
<?php
define('DB_HOST', '$(php_escape "${DB_HOST}")');
define('DB_NAME', '$(php_escape "${DB_NAME}")');
define('DB_USER', '$(php_escape "${DB_USER}")');
define('DB_PASS', '$(php_escape "${DB_PASS}")');
EOF
  chmod 600 "${SECRETS_FILE}" 2>/dev/null || true
fi

cat <<EOF
Deployment files are ready.

Database config: ${SECRETS_FILE}
Schema file:     ${SCHEMA_FILE}

Next steps:
  1. Create the database if it does not already exist.
  2. Import the schema:
     mysql -h ${DB_HOST} -u ${DB_USER} -p ${DB_NAME} < ${SCHEMA_FILE}
  3. Point your web server at:
     ${REPO_ROOT}

If you need different database credentials, rerun this command with DB_HOST, DB_NAME, DB_USER, and DB_PASS set in the environment after removing ${SECRETS_FILE}.
EOF
