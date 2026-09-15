#!/usr/bin/env bash
# Optional: install PHPUnit for local tests (no production vendor required).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
composer install --ignore-platform-reqs
echo "Dev dependencies ready (phpunit)."
