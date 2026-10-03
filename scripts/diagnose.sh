#!/usr/bin/env bash
# ==============================================================================
# Radio Agro - Fast CLI Diagnostic & Health Check Tool
# ==============================================================================

set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

# Allow running with or without --repair
php "$DIR/scripts/diagnose.php" "$@"
