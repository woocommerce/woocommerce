#!/bin/bash
# Pull woocommerce/all-plugins and unzip changed plugins into extracted/.
set -euo pipefail

REPO="${ALL_PLUGINS_DIR:-$HOME/Development/all-plugins}"
OUT="$REPO/extracted"

if [ ! -d "$REPO/.git" ]; then
	git clone --filter=blob:none https://github.com/woocommerce/all-plugins.git "$REPO"
	echo extracted/ >> "$REPO/.git/info/exclude"
fi

[ "${1:-}" = "--no-pull" ] || git -C "$REPO" pull --ff-only --quiet

mkdir -p "$OUT"

extract_one() {
	zip="$1"
	name="$(basename "$(dirname "$zip")")"
	dest="$OUT/$name"
	stamp="$dest/.zip-stamp"
	[ -f "$stamp" ] && [ "$stamp" -nt "$zip" ] && return 0
	rm -rf "$dest"
	mkdir -p "$dest"
	# Text files only: images, fonts and archives slow the search down.
	unzip -qq -o "$zip" -d "$dest" \
		'*.php' '*.inc' '*.js' '*.jsx' '*.ts' '*.tsx' '*.mjs' '*.json' \
		'*.css' '*.scss' '*.html' '*.twig' '*.xml' '*.yml' '*.yaml' \
		'*.sql' '*.txt' '*.md' 2>/dev/null || true
	touch "$stamp"
}
export OUT
export -f extract_one

find "$REPO/product-packages" -name '*.zip' -print0 | xargs -0 -n1 -P8 bash -c 'extract_one "$0"'

# Remove plugins that the repo no longer has.
for dir in "$OUT"/*/; do
	name="$(basename "$dir")"
	[ -d "$REPO/product-packages/$name" ] || rm -rf "$dir"
done

echo "Plugins: $(find "$OUT" -mindepth 1 -maxdepth 1 -type d | wc -l | tr -d ' ')  Updated: $(git -C "$REPO" log -1 --format=%cd --date=short)"
