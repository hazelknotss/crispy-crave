#!/usr/bin/env bash
# Run on Linux / WSL (not Windows CMD). Uses PHP only - no Apache, avoids 403 from httpd.
#   chmod +x php-dev-server.sh
#   ./php-dev-server.sh
#   ./php-dev-server.sh 9000
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"
PORT="${1:-8888}"
echo ""
echo "  PHP dev server (no Apache)"
echo "  Root: $ROOT"
echo "  URL:  http://127.0.0.1:${PORT}/"
echo "  LAN:  http://YOUR_LAN_IP:${PORT}/"
echo "  Ctrl+C to stop."
echo ""
exec php -S "0.0.0.0:${PORT}" -t "$ROOT"
