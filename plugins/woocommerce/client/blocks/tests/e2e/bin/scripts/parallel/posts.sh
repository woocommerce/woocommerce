#!/usr/bin/env bash

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && cd ../../posts/ && pwd)"

function create_post {
	name=$(basename -s .html $1) # Remove .html extension
	title=$(IFS=- read -ra str <<<"$name"; printf '%s' "${str[*]^}") # Capitalize each word
	wp post create \
		--post_status=publish \
		--post_author=1 \
		--post_title="$title block" \
		$1
}
export -f create_post

find $script_dir -maxdepth 1 -type f | xargs -P5 -n1 bash -c 'create_post "$@"' _

