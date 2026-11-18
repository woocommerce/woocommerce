#!/usr/bin/env bash
#
# Prints the plugin's tooling metadata: package.json `config.woo` with every default applied.
# Shared scripts and workflows read per-plugin values only through this script, so the defaults
# live in one place. woo-config.schema.json, next to this script, documents each key; the object
# is validated against it first, so a misspelled key or a wrong type fails here.
#
# Run it from the plugin root, the folder with package.json.
#
# Usage:
#   woo-extension-config                  Normalized config as JSON.
#   woo-extension-config '<jq filter>'    One value, e.g. '.zip.root' (raw output).
#   woo-extension-config --github-output  Writes `json=<config>` to $GITHUB_OUTPUT.
#   woo-extension-config --phpunit-matrix PHPUnit legs as a JSON array. See README.md for inputs.
set -euo pipefail

# npm links executables from node_modules/.bin. The schema lives beside the script target.
reader=$0
while [[ -L "$reader" ]]; do
	directory=$(dirname "$reader")
	reader=$(readlink "$reader")
	[[ "$reader" == /* ]] || reader="$directory/$reader"
done
schema="$(dirname "$reader")/woo-config.schema.json"

if [[ ! -f package.json ]]; then
	echo 'No package.json here. Run this from the plugin root.' >&2
	exit 1
fi

if ! command -v jq >/dev/null; then
	echo 'jq is required to read extension config.' >&2
	exit 1
fi

# The subset of JSON Schema that woo-config.schema.json uses: type, enum, pattern, required,
# properties, additionalProperties, items and $ref into $defs.
errors=$(jq -r --slurpfile schema "$schema" '
	def check($node; $path):
		(if $node | has("$ref") then $schema[0]["$defs"][$node["$ref"] | split("/") | last] else $node end) as $s
		| . as $value
		| type as $type
		| if $s.type != null and ((if $s.type == "integer" then $type == "number" and $value == ($value | floor) else $type == $s.type end) | not) then
			"\($path): expected \($s.type), got \($type)"
		else
			(if $s.enum != null and ($s.enum | any(. == $value) | not) then "\($path): \($value | tojson) is not one of \($s.enum | tojson)" else empty end),
			(if $type == "string" and $s.pattern != null and ($value | test($s.pattern) | not) then "\($path): \($value | tojson) does not match \($s.pattern)" else empty end),
			(if $type == "object" then
				(($s.required // [])[] | select(. as $key | $value | has($key) | not) | "\($path): missing \(.)"),
				($value | to_entries[] | .key as $key | .value
					| if ($s.properties // {}) | has($key) then check($s.properties[$key]; "\($path).\($key)")
					elif $s.additionalProperties == false then "\($path): unknown key \($key)"
					elif ($s.additionalProperties | type) == "object" then check($s.additionalProperties; "\($path).\($key)")
					else empty end)
			else empty end),
			(if $type == "array" and $s.items != null then
				($value | to_entries[] | .key as $index | .value | check($s.items; "\($path)[\($index)]"))
			else empty end)
		end;
	(if (.config | type) == "object" and (.config | has("sww")) then "config.sww: renamed to config.woo" else empty end),
	(if (.config | type) == "object" and (.config | has("woo")) then .config.woo | check($schema[0]; "config.woo") else empty end)
' package.json) || {
	echo 'package.json is not valid JSON, or woo-config.schema.json is missing.' >&2
	exit 1
}
if [[ -n "$errors" ]]; then
	echo 'Invalid package.json tooling config:' >&2
	sed 's/^/  /' <<< "$errors" >&2
	exit 1
fi

config=$(jq -ce '
	# A key the object has, else the default. Unlike `//`, keeps false, 0 and "".
	def opt($key; $default): if type == "object" and has($key) then .[$key] else $default end;
	(.config.woo // {}) as $c
	| .name as $package
	| ($c | opt("slug"; $package)) as $name
	| if ($name | type) != "string" then error("no slug") else . end
	| ($c | opt("mainFile"; $name + ".php")) as $main
	| ($c | opt("capabilities"; {})) as $cap
	| ($c | opt("zip"; {})) as $zip
	| ($c | opt("pluginCheck"; {})) as $pc
	| ($c | opt("qit"; {})) as $qit
	| ($c | opt("e2e"; {})) as $e2e
	| ($c | opt("phpunit"; {})) as $unit
	| ($c | opt("release"; {})) as $rel
	| ($c | opt("phpLint"; {})) as $lint
	| ($c | opt("makepot"; {})) as $pot
	| {
		slug: $name,
		version: .version,
		mainFile: $main,
		productName: ($c | opt("productName"; $name)),
		capabilities: {
			js: ($cap | opt("js"; true)),
			jsTests: ($cap | opt("jsTests"; false)),
			buildTests: ($cap | opt("buildTests"; false)),
			e2e: ($cap | opt("e2e"; false)),
			phpunit: ($cap | opt("phpunit"; true)),
			isolated: ($cap | opt("isolated"; false)),
			integration: ($cap | opt("integration"; false)),
			qit: ($cap | opt("qit"; true)),
			pluginCheck: ($cap | opt("pluginCheck"; true))
		},
		zip: {
			root: ($zip | opt("root"; $name)),
			exclude: ($zip | opt("exclude"; "exclude.lst")),
			build: ($zip | opt("build"; "build")),
			composerProduction: ($zip | opt("composerProduction"; false)),
			php: ($zip | opt("php"; "8.4")),
			phpCompatibility: ($zip | opt("phpCompatibility"; null))
		},
		versionPatterns: [ ($c | opt("versionPatterns"; []))[] | { file: opt("file"; $main), pattern: .pattern } ],
		pluginCheck: {
			excludeChecks: ($pc | opt("excludeChecks"; [])),
			excludeDirectories: ($pc | opt("excludeDirectories"; [])),
			excludeFiles: ($pc | opt("excludeFiles"; [])),
			ignoreCodes: ($pc | opt("ignoreCodes"; [])),
			ignoreWarnings: ($pc | opt("ignoreWarnings"; false)),
			strict: ($pc | opt("strict"; false)),
			timeoutMinutes: ($pc | opt("timeoutMinutes"; 10))
		},
		phpLint: {
			php: ($lint | opt("php"; [ "8.4" ])),
			isolatedPhp: ($lint | opt("isolatedPhp"; [ "8.4" ]))
		},
		makepot: {
			exclude: ($pot | opt("exclude"; [])),
			headers: ($pot | opt("headers"; {}))
		},
		qit: {
			extension: ($qit | opt("extension"; $name)),
			plugins: ($qit | opt("plugins"; "")),
			derivativeExclude: ($qit | opt("derivativeExclude"; "")),
			apiFuzz: ($qit | opt("apiFuzz"; true)),
			timeoutMinutes: ($qit | opt("timeoutMinutes"; 100))
		},
		e2e: {
			projects: ($e2e | opt("projects"; [ "" ])),
			timeoutMinutes: ($e2e | opt("timeoutMinutes"; 20)),
			privatePlugins: ($e2e | opt("privatePlugins"; false))
		},
		phpunit: {
			timeoutMinutes: ($unit | opt("timeoutMinutes"; 10)),
			phpInput: ($unit | opt("phpInput"; "ignore")),
			ini: ($unit | opt("ini"; "")),
			scheduledWc: ($unit | opt("scheduledWc"; "")),
			passEnv: ($unit | opt("passEnv"; [])),
			legs: ($unit | opt("legs"; [
				{ php: "7.4", wp: "latest", wc: "latest" },
				{ php: "8.4", wp: "latest", wc: "latest", coverage: true },
				{ php: "8.5", wp: "latest", wc: "latest" }
			]))
		},
		integration: {
			timeoutMinutes: ($c | opt("integration"; {}) | opt("timeoutMinutes"; 20)),
			legs: ($c | opt("integration"; {}) | opt("legs"; []))
		},
		privatePlugins: [ ($c | opt("privatePlugins"; []))[] | {
			slug: .slug,
			source: opt("source"; "release"),
			suites: opt("suites"; [ "unit" ]),
			dirEnv: opt("dirEnv"; ""),
			requireEnv: opt("requireEnv"; ""),
			hookOnly: opt("hookOnly"; false),
			minimumPattern: opt("minimumPattern"; "")
		} ],
		pullRequests: {
			privatePlugins: ($c | opt("pullRequests"; {}) | opt("privatePlugins"; false))
		},
		release: {
			target: ($rel | opt("target"; "woocommerce.com")),
			productId: ($rel | opt("productId"; "")),
			readmeChangelog: ($rel | opt("readmeChangelog"; false)),
			wpOrgSlug: ($rel | opt("wpOrgSlug"; ""))
		}
	}
' package.json) || {
	echo 'package.json must have a "name" or a config.woo.slug.' >&2
	exit 1
}

if [[ ! "$(jq -r .slug <<< "$config")" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]]; then
	echo 'The plugin slug must be lowercase words joined by hyphens. Set config.woo.slug when the package.json name is not the slug.' >&2
	exit 1
fi

# Turns config.woo.phpunit.legs into concrete jobs. A leg's `wp` and `wc` are `latest`,
# `previous` (WordPress only: the newest release of the previous major series, from
# $PREVIOUS_WP), `minimum` (the main file's "Requires at least" / "WC requires at least") or a
# version. A requested WordPress or WooCommerce version replaces `latest` and `previous`. A
# requested PHP version replaces every leg's PHP when phpunit.phpInput is "replace", except on
# legs with a `minimum` or fixed WordPress version. Legs with `"defaultOnly": true` run only
# when no WordPress or PHP version is requested, and legs with `"scheduledOnly": true` only when
# SCHEDULED=true (Cron CI). A leg's `plugins` picks a private plugin release: `latest`, `minimum`
# (that plugin's minimumPattern in the main file), `below-minimum` (the newest release older than
# the minimum) or a tag; the leg's `pluginVersions` carries it to the download step (`<x.y.z` for
# below-minimum). Identical legs are merged. SUITE=integration reads config.woo.integration.legs.
phpunit_matrix() {
	local main_file wp_minimum wc_minimum minimums='{}' slug pattern prefix suffix line version
	main_file=$(jq -r .mainFile <<< "$config")
	wp_minimum=$(awk -F ': ' '/^[[:space:]]*\* Requires at least:/{print $2; exit}' "$main_file" | tr -d '\r')
	wc_minimum=$(awk -F ': ' '/^[[:space:]]*\* WC requires at least:/{print $2; exit}' "$main_file" | tr -d '\r')
	[[ ! "$wc_minimum" =~ ^[0-9]+\.[0-9]+$ ]] || wc_minimum="$wc_minimum.0"
	# NUL separators preserve the pattern's literal text.
	while IFS= read -r -d '' slug && IFS= read -r -d '' pattern; do
		prefix=${pattern%%%s*}
		suffix=${pattern#*%s}
		line=$(grep -F -m1 -- "$prefix" "$main_file") || { echo "No minimumPattern match for $slug in $main_file." >&2; return 1; }
		line=${line%$'\r'}
		version=${line#*"$prefix"}
		if [[ -n "$suffix" ]]; then
			version=${version%%"$suffix"*}
		fi
		[[ "$version" =~ ^[0-9]+(\.[0-9]+)*$ ]] || { echo "Invalid minimum $slug version '$version' in $main_file." >&2; return 1; }
		minimums=$(jq -c --arg slug "$slug" --arg version "$version" '. + { ($slug): $version }' <<< "$minimums")
	done < <(jq -j '.privatePlugins[] | select(.minimumPattern != "") | .slug, "\u0000", .minimumPattern, "\u0000"' <<< "$config")
	jq -c \
		--arg wp "${REQUESTED_WP:-}" --arg wc "${REQUESTED_WC:-}" --arg php "${REQUESTED_PHP:-}" \
		--arg previous "${PREVIOUS_WP:-}" --arg wpmin "$wp_minimum" --arg wcmin "$wc_minimum" \
		--arg suite "${SUITE:-unit}" --arg scheduled "${SCHEDULED:-false}" --argjson minimums "$minimums" '
		.phpunit.phpInput as $phpInput
		| (if $suite == "integration" then .integration else .phpunit end) as $u
		| ($wp != "" or $php != "") as $requested
		| [ .privatePlugins[].slug ] as $private
		| [ $u.legs[]
			| select(($requested and (.defaultOnly // false)) | not)
			| select((.scheduledOnly // false) | not or $scheduled == "true")
			| (.plugins // {}) as $plugins
			| ($plugins | to_entries | map(select(.value != "latest"))) as $pinned
			| ($pinned | map(.key as $slug | .value as $spec | { key: $slug, value: (
				if ($private | any(. == $slug) | not) then error("Leg plugin \($slug) is not in config.woo.privatePlugins")
				elif $spec == "minimum" or $spec == "below-minimum" then
					($minimums[$slug] // error("config.woo.privatePlugins \($slug) needs a minimumPattern"))
					| if $spec == "below-minimum" then "<" + . else . end
				else $spec end) }) | from_entries) as $pluginVersions
			| (.wp // "latest") as $legwp
			| (.wc // "latest") as $legwc
			| ($legwp == "latest" or $legwp == "previous") as $floating
			| {
				php: (if $php != "" and $phpInput == "replace" and $floating then $php else .php end),
				wp: (if $legwp == "minimum" then $wpmin
					elif $floating and $wp != "" then $wp
					elif $legwp == "previous" then $previous
					else $legwp end),
				wc: (if $legwc == "minimum" then $wcmin
					elif $legwc == "latest" and $wc != "" then $wc
					else $legwc end),
				script: (.script // "test:php:unit"),
				provision: (.provision // "install"),
				coverage: (.coverage // false),
				env: (.env // {}),
				pluginVersions: $pluginVersions,
				floor: ($legwp == "minimum")
			}
			| . + { label: "PHP \(.php) / WP \(.wp) / WC \(.wc)\(if (.env | length) > 0 then " / " + (.env | to_entries | map("\(.key)=\(.value)") | join(", ")) else "" end)\($pinned | map(" / \(.key) \(.value)") | join(""))\(if .script != "test:php:unit" then " / " + .script else "" end)" }
		]
		| reduce .[] as $leg ([]; if any(.[]; .label == $leg.label) then . else . + [ $leg ] end)
	' <<< "$config"
}

case "${1:-}" in
	'')
		printf '%s\n' "$config"
		;;
	--github-output)
		printf 'json=%s\n' "$config" >> "$GITHUB_OUTPUT"
		;;
	--phpunit-matrix)
		phpunit_matrix
		;;
	*)
		jq -r "$1" <<< "$config"
		;;
esac
