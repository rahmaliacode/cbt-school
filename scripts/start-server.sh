#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
CBT_PHP="${CBT_PHP:-php}"
"$CBT_PHP" artisan schedule:work &
CBT_SCHEDULER_PID=$!
trap 'kill "$CBT_SCHEDULER_PID" 2>/dev/null || true' EXIT INT TERM
"$CBT_PHP" artisan serve --host=0.0.0.0 --port=8000
