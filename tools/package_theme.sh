#!/usr/bin/env bash
# Build an installable zip of the WordPress theme: dist/alpha-preclinical-<version>.zip
# Upload it at Appearance > Themes > Add New > Upload Theme, or unzip into wp-content/themes/.
set -euo pipefail
cd "$(dirname "$0")/.."
VERSION=$(grep -m1 '^Version:' wordpress/alpha-preclinical/style.css | awk '{print $2}')
mkdir -p dist
OUT="dist/alpha-preclinical-${VERSION}.zip"
rm -f "$OUT"
(cd wordpress && zip -qr "../$OUT" alpha-preclinical -x '*/.DS_Store' '*/__pycache__/*')
echo "$OUT"
