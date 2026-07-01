#!/usr/bin/env bash

##
# Replace the `$$next-version$$` placeholder token with a concrete version number.
#
# Contributors write `$$next-version$$` instead of hardcoding a version in `@since`,
# `@deprecated`, and deprecation function calls. This script swaps the token for the
# real version at release time (see .github/workflows/release-code-freeze.yml and
# release-bump-version.yml).
#
# Adapted from Jetpack's tools/replace-next-version-tag.sh, with WooCommerce's own
# deprecation wrappers (wc_deprecated_*, wc_doing_it_wrong) added.
##

set -eo pipefail

function usage {
	cat <<-'EOH'
		usage: replace-next-version-tag.sh [-c] [-v] <version> [path]

		Replace the `$$next-version$$` token with <version> in tracked files under <path>
		(default: plugins/woocommerce). Recognized patterns:

		 - `@since $$next-version$$`
		 - `@deprecated $$next-version$$`
		 - `@deprecated since $$next-version$$`
		 - `_deprecated_function( ..., '...$$next-version$$' )` and the other WordPress
		   `_deprecated_*` / `_doing_it_wrong()` functions.
		 - `wc_deprecated_function( ..., '...$$next-version$$' )` and the other WooCommerce
		   `wc_deprecated_*` / `wc_doing_it_wrong()` wrappers.
		 - `do_action_deprecated()` / `apply_filters_deprecated()`

		   For the function-call patterns the call must be on one line, tab-indented, and the
		   `$$next-version$$` token must live in a single-quoted string.

		Options:
		 -c  Check only. Do not modify files; exit non-zero if any token is found.
		 -v  Verbose. Print each file processed.
	EOH
	exit 1
}

CHECK_ONLY=
VERBOSE=
while getopts ":cvh" opt; do
	case ${opt} in
		c) CHECK_ONLY=1 ;;
		v) VERBOSE=1 ;;
		h) usage ;;
		?)
			echo "Invalid argument: -$OPTARG" >&2
			usage
			;;
	esac
done
shift "$(( OPTIND - 1 ))"

[[ -z "$1" ]] && { echo "A version must be specified." >&2; usage; }
VERSION="$1"
PATH_ARG="${2:-plugins/woocommerce}"

if ! grep -E -q '^[0-9]+(\.[0-9]+)+(-[a-z0-9.]+)?$' <<<"$VERSION"; then
	echo "Warning: \"$VERSION\" does not look like a version number. Continuing anyway." >&2
fi

# Escape the version for use in a sed replacement.
VE=$(sed 's/[&\\/]/\\&/g' <<<"$VERSION")

function debug {
	[[ -n "$VERBOSE" ]] && echo "$@" >&2
	return 0
}

# Run from the repo root so the path argument resolves predictably.
cd "$(git rev-parse --show-toplevel)"

EXIT=0
# NUL-delimited so pathnames containing whitespace are handled correctly.
while IFS= read -r -d '' FILE; do
	grep -F -q '$$next-version$$' "$FILE" 2>/dev/null || continue
	debug "Processing $FILE"

	if [[ -z "$CHECK_ONLY" ]]; then
		# Docblock tags: @since / @deprecated / @deprecated since.
		sed -i.bak -E -e 's!(@since|@deprecated( +[sS]ince)?)( +)\$\$next-version\$\$!\1\3'"$VE"'!g' "$FILE"
		rm "$FILE.bak" # macOS sed requires a backup suffix.

		# WordPress + WooCommerce deprecation functions (version in a single-quoted string).
		sed -i.bak -E -e $'s!(^\t*(wc_deprecated_(function|hook|argument)|_deprecated_(function|constructor|file|argument|hook|class))\\( .*, \'[^\']*)\\$\\$next-version\\$\\$\'!\\1'"$VE"$'\'!g' "$FILE"
		rm "$FILE.bak"

		# do_action_deprecated() / apply_filters_deprecated().
		sed -i.bak -E -e $'s!((do_action|apply_filters)_deprecated\\( .*, \'[^\']*)\\$\\$next-version\\$\\$\'!\\1'"$VE"$'\'!g' "$FILE"
		rm "$FILE.bak"

		# _doing_it_wrong() / wc_doing_it_wrong() (version is the third argument).
		sed -i.bak -E -e $'s!(^\t*(wc_doing_it_wrong|_doing_it_wrong)\\( .*, .*, \'[^\']*)\\$\\$next-version\\$\\$\'!\\1'"$VE"$'\'!g' "$FILE"
		rm "$FILE.bak"
	fi

	# Report any token we could not place in a recognized context.
	if grep -F -q '$$next-version$$' "$FILE"; then
		EXIT=1
		while IFS=':' read -r LINE _; do
			[[ -z "$LINE" ]] && continue
			if [[ -n "$CI" ]]; then
				echo "::error file=$FILE,line=$LINE::"'Unexpected `$$next-version$$` token.'
			else
				echo "$FILE:$LINE: Unexpected \`\$\$next-version\$\$\` token." >&2
			fi
		done < <( grep --line-number -F '$$next-version$$' "$FILE" || true )
	fi
done < <( git ls-files -z "$PATH_ARG" )

exit $EXIT
