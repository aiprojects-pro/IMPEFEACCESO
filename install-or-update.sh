#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 || $# -gt 2 || ( $# -eq 2 && "$2" != "--with-core-overrides" ) ]]; then
  echo "Uso: $0 MOODLE_ROOT [--with-core-overrides]" >&2
  exit 1
fi

MOODLE_ROOT="$1"
WITH_CORE_OVERRIDES="${2:-}"

if [[ ! -f "$MOODLE_ROOT/config.php" ]]; then
  echo "No existe config.php en $MOODLE_ROOT" >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

mkdir -p "$MOODLE_ROOT/local"
rsync -a --delete "$SCRIPT_DIR/local/impefeverif/" "$MOODLE_ROOT/local/impefeverif/"

# This repository contains only the IMPEFE overlay, never a complete Moove theme.
rsync -a "$SCRIPT_DIR/theme/moove/" "$MOODLE_ROOT/theme/moove/"

if [[ "$WITH_CORE_OVERRIDES" == "--with-core-overrides" ]]; then
  MOODLE_VERSION="$(php -r "define('CLI_SCRIPT', true); require '$MOODLE_ROOT/config.php'; echo \\$CFG->version;")"
  if [[ "$MOODLE_VERSION" != "2026042001.04" ]]; then
    echo "La sobreescritura de login/confirm.php solo está validada para Moodle 2026042001.04." >&2
    echo "Se ha instalado el plugin y la capa del tema, pero no se ha tocado el core." >&2
    exit 2
  fi

  CORE_BACKUP="$MOODLE_ROOT/login/confirm.php.pre-impefe-$(date -u +%Y%m%dT%H%M%SZ)"
  cp -p "$MOODLE_ROOT/login/confirm.php" "$CORE_BACKUP"
  install -m 0644 "$SCRIPT_DIR/core-overrides/login/confirm.php" "$MOODLE_ROOT/login/confirm.php"
  echo "Backup de login/confirm.php: $CORE_BACKUP"
fi

php "$MOODLE_ROOT/admin/cli/upgrade.php" --non-interactive --lang=es
php "$MOODLE_ROOT/local/impefeverif/cli/apply_access_setup.php"
php "$MOODLE_ROOT/local/impefeverif/cli/apply_submission_notices.php"
php "$MOODLE_ROOT/admin/cli/purge_caches.php"
php "$MOODLE_ROOT/admin/cli/checks.php"
