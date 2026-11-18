#!/usr/bin/env bash
#
# Packages the plugin as deploy/<slug>.zip with one root folder, config.woo.zip.root
# (default: the slug).
#
# Usage: woo-extension-tooling build-zip [<commit>], from the plugin root.
#   Without a commit: runs the config.woo.zip.build package script (default `build`; empty for
#   none), then packages the current checkout, uncommitted and untracked files included.
#   With a commit: packages the files committed at it, for plugins without a build step.
#
# Steps: copy the source into a staging folder (never .git, node_modules, deploy or vendor),
# install production Composer dependencies there when config.woo.zip.composerProduction is true,
# run bin/hooks/build-zip-stage.sh <staging folder> when the repository has it, then zip the
# staging folder without the paths that the exclude list (config.woo.zip.exclude, default
# exclude.lst) names. The exclude list has one `zip -x` wildcard per line, matched against
# `<root>/<path>`; a leading `/` anchors a line at the plugin root, and `#` starts a comment.
set -euo pipefail

root=$PWD

config=$(bash "$(dirname "$0")/woo-config.sh")
name=$(jq -r .slug <<< "$config")
plugin_dir=$(jq -r .zip.root <<< "$config")
case "$plugin_dir" in
	''|.|..|-*|*/*|*\\*)
		echo 'zip.root must be a single folder name without path separators or a leading hyphen.' >&2
		exit 1
		;;
esac
exclude=$(jq -r .zip.exclude <<< "$config")
build=$(jq -r '.zip.build // ""' <<< "$config")
composer_production=$(jq -r .zip.composerProduction <<< "$config")
ref=${1:-}

work=$(mktemp -d)
trap 'rm -rf -- "$work"' EXIT

source_dir=$root
if [[ -n "$ref" ]]; then
	if [[ -n "$build" ]]; then
		echo "Packaging a commit needs a plugin without a build step (config.woo.zip.build is \"$build\"). Check the commit out and run the ZIP build there." >&2
		exit 1
	fi
	commit=$(git rev-parse --verify --end-of-options "$ref^{commit}")
	git diff --quiet "$commit" -- package.json || { echo "package.json differs from $commit; check the commit out instead." >&2; exit 1; }
	source_dir=$work/source
	mkdir "$source_dir"
	git archive --format=tar "$commit" | tar -x -C "$source_dir"
elif [[ -n "$build" ]]; then
	pnpm run "$build"
fi

if [[ ! -f "$source_dir/$exclude" ]]; then
	echo "Missing exclude list: $exclude" >&2
	exit 1
fi

stage=$work/stage/$plugin_dir
mkdir -p "$stage"
rsync -a --exclude=/.git --exclude=/node_modules --exclude=/deploy --exclude=/vendor "$source_dir/" "$stage/"

if [[ "$composer_production" == true ]]; then
	# Composer reads the package's Git version and revision through this pointer.
	if git_dir=$(git -C "$root" rev-parse --absolute-git-dir 2>/dev/null); then
		printf 'gitdir: %s\n' "$git_dir" > "$stage/.git"
	fi
	(cd "$stage" && composer install --no-dev --prefer-dist --no-progress --no-interaction)
	rm -f "$stage/.git"
fi

if [[ -f "$source_dir/bin/hooks/build-zip-stage.sh" ]]; then
	(cd "$source_dir" && bash bin/hooks/build-zip-stage.sh "$stage")
fi

# Anchor `/` lines at the plugin folder and drop comments and blank lines.
patterns=$work/exclude.lst
while IFS= read -r line || [[ -n "$line" ]]; do
	line=${line%$'\r'}
	[[ -z "$line" || "$line" == \#* ]] && continue
	if [[ "$line" == /* ]]; then
		line="$plugin_dir$line"
	fi
	printf '%s\n' "$line"
done < "$source_dir/$exclude" > "$patterns"

(cd "$work/stage" && zip -qr "$work/plugin.zip" "$plugin_dir" -x "@$patterns")

mkdir -p deploy
mv "$work/plugin.zip" "deploy/$name.zip"
echo "Built deploy/$name.zip"
