#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Uso: $0 MOODLE_ROOT" >&2
  exit 1
fi

MOODLE_ROOT="$1"

if [[ ! -f "$MOODLE_ROOT/config.php" ]]; then
  echo "No existe config.php en $MOODLE_ROOT" >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

mkdir -p "$MOODLE_ROOT/local"
rsync -a --delete "$SCRIPT_DIR/local/impefeverif/" "$MOODLE_ROOT/local/impefeverif/"

php "$MOODLE_ROOT/admin/cli/upgrade.php" --non-interactive --lang=es
php "$MOODLE_ROOT/local/impefeverif/cli/apply_access_setup.php"
php "$MOODLE_ROOT/admin/cli/purge_caches.php"
php "$MOODLE_ROOT/admin/cli/checks.php"
