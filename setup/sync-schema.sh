#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

cp "${SCRIPT_DIR}/schema.sql" "${REPO_ROOT}/framework/schema.sql"
printf 'Synced %s -> %s\n' "${SCRIPT_DIR}/schema.sql" "${REPO_ROOT}/framework/schema.sql"
