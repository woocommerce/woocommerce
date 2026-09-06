#!/usr/bin/env bash

set -euo pipefail

fail() {
	printf 'FAIL: %s\n' "$*" >&2
	exit 1
}

script_directory="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" && pwd )"
repository_root="$( cd -- "${script_directory}/../../.." && pwd )"
helper="${script_directory}/check-doc-links.js"
config="${repository_root}/.github/workflows/check-doc-links-config.json"
workflow="${repository_root}/.github/workflows/ci.yml"
temporary_root="${TMPDIR:-${RUNNER_TEMP:-}}"
[[ -n "${temporary_root}" ]] || fail 'temporary directory root is unavailable; set TMPDIR or RUNNER_TEMP'
temporary_root="${temporary_root%/}"
[[ -n "${temporary_root}" ]] || fail 'temporary directory root is unavailable; set TMPDIR or RUNNER_TEMP'
temporary_directory="$( mktemp -d "${temporary_root}/check-doc-links.XXXXXX" )"

cleanup() {
	rm -rf -- "${temporary_directory}"
}
trap cleanup EXIT

assert_file_absent() {
	local path="$1"
	local message="$2"

	[[ ! -e "${path}" ]] || fail "${message}"
}

[[ -f "${helper}" ]] || fail "link-check helper is missing: ${helper}"
[[ -f "${config}" ]] || fail "link-check config is missing: ${config}"
[[ -f "${workflow}" ]] || fail "CI workflow is missing: ${workflow}"

mock_bin="${temporary_directory}/bin"
mkdir -p -- "${mock_bin}"
{
	printf '%s\n' '#!/usr/bin/env bash'
	printf '%s\n' 'printf '\''%s\0'\'' "$@" > "${PNPM_ARGUMENTS_FILE:?}"'
	printf '%s\n' 'exit "${PNPM_EXIT_STATUS:-0}"'
} > "${mock_bin}/pnpm"
chmod +x "${mock_bin}/pnpm"

arguments_file="${temporary_directory}/arguments"
PATH="${mock_bin}:${PATH}" PNPM_ARGUMENTS_FILE="${arguments_file}" node "${helper}"
assert_file_absent "${arguments_file}" 'pnpm was invoked when no filenames were provided'

DOC_LINK_FILES_JSON='[]' PATH="${mock_bin}:${PATH}" PNPM_ARGUMENTS_FILE="${arguments_file}" node "${helper}"
assert_file_absent "${arguments_file}" 'pnpm was invoked for an empty JSON file list'

filenames=(
	'docs/path with spaces.md'
	"docs/single'quote.md"
	'docs/double"quote.md'
	'docs/*.md'
	'-leading-dash.md'
	'docs/semi;colon.md'
)

PATH="${mock_bin}:${PATH}" PNPM_ARGUMENTS_FILE="${arguments_file}" node "${helper}" "${filenames[@]}"

expected_arguments_file="${temporary_directory}/expected-arguments"
printf '%s\0' \
	'dlx' \
	'markdown-link-check@3.14.2' \
	'--quiet' \
	'--config' \
	"${config}" \
	'--' \
	"${filenames[@]}" > "${expected_arguments_file}"

cmp -- "${expected_arguments_file}" "${arguments_file}" || fail 'CLI filenames were not passed as the required NUL-delimited argv'

injection_marker="${temporary_directory}/injected"
newline_filename=$'docs/guide.md\nprintf INJECTED > '"${injection_marker}"
json_filenames=( "${filenames[@]}" "${newline_filename}" )
files_json="$( node -e 'process.stdout.write( JSON.stringify( process.argv.slice( 1 ) ) );' "${json_filenames[@]}" )"
rm -- "${arguments_file}"

DOC_LINK_FILES_JSON="${files_json}" PATH="${mock_bin}:${PATH}" PNPM_ARGUMENTS_FILE="${arguments_file}" node "${helper}"
printf '%s\0' \
	'dlx' \
	'markdown-link-check@3.14.2' \
	'--quiet' \
	'--config' \
	"${config}" \
	'--' \
	"${json_filenames[@]}" > "${expected_arguments_file}"

cmp -- "${expected_arguments_file}" "${arguments_file}" || fail 'JSON filenames were not passed as the required NUL-delimited argv'
assert_file_absent "${injection_marker}" 'a filename from the JSON file list was interpreted as shell code'

for invalid_json in 'not JSON' '{}' '[1]'; do
	rm -f -- "${arguments_file}"
	if DOC_LINK_FILES_JSON="${invalid_json}" PATH="${mock_bin}:${PATH}" PNPM_ARGUMENTS_FILE="${arguments_file}" node "${helper}" 2> "${temporary_directory}/error"; then
		fail "invalid file-list JSON was accepted: ${invalid_json}"
	fi
	assert_file_absent "${arguments_file}" "pnpm was invoked for invalid file-list JSON: ${invalid_json}"
	diff -u --label expected --label actual \
		<( printf '%s\n' 'FAIL: DOC_LINK_FILES_JSON must be a JSON array of strings' ) \
		"${temporary_directory}/error" || fail "invalid file-list JSON produced the wrong diagnostic: ${invalid_json}"
done

rm -f -- "${arguments_file}"
if DOC_LINK_FILES_JSON='["docs/guide.md"]' PATH="${mock_bin}:${PATH}" PNPM_ARGUMENTS_FILE="${arguments_file}" PNPM_EXIT_STATUS=7 node "${helper}"; then
	fail 'the helper did not propagate the link checker exit status'
else
	status=$?
	[[ "${status}" -eq 7 ]] || fail "expected link checker exit status 7, got ${status}"
fi

CONFIG_FILE="${config}" WORKFLOW_FILE="${workflow}" node <<'NODE'
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );

const workflow = fs.readFileSync( process.env.WORKFLOW_FILE, 'utf8' );
const workflowLines = workflow.split( /\r?\n/ );
const indentation = ( line ) => line.match( /^ */ )[ 0 ].length;
const findLine = ( expectedLine, startIndex = 0 ) => {
	const index = workflowLines.indexOf( expectedLine, startIndex );
	assert.notEqual( index, -1, `workflow must contain: ${ expectedLine.trim() }` );
	return index;
};
const block = ( startIndex ) => {
	const parentIndentation = indentation( workflowLines[ startIndex ] );
	let endIndex = startIndex + 1;
	while (
		endIndex < workflowLines.length &&
		( workflowLines[ endIndex ].trim() === '' || indentation( workflowLines[ endIndex ] ) > parentIndentation )
	) {
		endIndex += 1;
	}
	return workflowLines.slice( startIndex + 1, endIndex ).map( ( line ) => line.trim() ).filter( Boolean );
};
const childValues = ( startIndex ) => block( startIndex ).filter( ( line ) => line.startsWith( '- ' ) ).map( ( line ) => line.slice( 2 ) );
const requireLines = ( lines, requiredLines, scope ) => {
	for ( const line of requiredLines ) {
		assert.ok( lines.includes( line ), `${ scope } must contain: ${ line }` );
	}
};

const markdownValidationFilter = findLine( '            needs-markdown-validation:' );
requireLines( childValues( markdownValidationFilter ), [
	"'.github/workflows/ci.yml'",
	"'.github/workflows/check-doc-links-config.json'",
	"'.github/workflows/scripts/check-doc-links.js'",
	"'.github/workflows/scripts/check-doc-links.test.sh'",
], 'needs-markdown-validation' );

const validateMarkdownJob = findLine( '  validate-markdown:' );
const validateMarkdownLines = block( validateMarkdownJob );
requireLines( validateMarkdownLines, [ 'runs-on: ubuntu-latest' ], 'validate-markdown' );
assert.equal( validateMarkdownLines.some( ( line ) => line.includes( 'WooCommerce Release Checks' ) ), false, 'validate-markdown must not use the runner group' );

const targetChangesId = findLine( '        id: target-changes', validateMarkdownJob );
const targetChangesLines = block( targetChangesId - 1 );
requireLines( targetChangesLines, [ 'list-files: shell' ], 'pre-existing Markdown path filter' );
assert.equal( targetChangesLines.some( ( line ) => line.includes( 'needs-doc-link-' ) ), false, 'doc-link filters must not use the shell formatter' );

const docLinkChangesId = findLine( '        id: doc-link-changes', validateMarkdownJob );
const docLinkChangesStep = docLinkChangesId - 1;
const docLinkChangesLines = block( docLinkChangesStep );
const docLinkChangesEnd = docLinkChangesStep + docLinkChangesLines.length + 1;
assert.equal(
	workflowLines[ docLinkChangesStep ].trim(),
	'- uses: dorny/paths-filter@7b450fff21473bca461d4b92ce414b9d0420d706 # v4.0.2',
	'doc-link path filtering must pin the action by full commit SHA'
);
requireLines( docLinkChangesLines, [ 'list-files: json' ], 'doc-link path filter' );

const docLinkValidationFilter = findLine( '            needs-doc-link-validation:', docLinkChangesStep );
const docLinkContractValidationFilter = findLine( '            needs-doc-link-contract-validation:', docLinkChangesStep );
assert.ok( docLinkValidationFilter < docLinkChangesEnd && docLinkContractValidationFilter < docLinkChangesEnd, 'doc-link filters must belong to the JSON path-filter step' );
assert.deepEqual( childValues( docLinkValidationFilter ), [ "added|modified: 'docs/**/*.md'" ], 'only added or modified docs Markdown files should be checked' );
assert.deepEqual( childValues( docLinkContractValidationFilter ), [
		"added|modified|deleted: '.github/workflows/check-doc-links-config.json'",
		"added|modified|deleted: '.github/workflows/scripts/check-doc-links.js'",
		"added|modified|deleted: '.github/workflows/scripts/check-doc-links.test.sh'",
		"added|modified|deleted: '.github/workflows/ci.yml'",
], 'contract changes must include additions, modifications, deletions, and rename sources' );

const markdownLintStep = findLine( "      - name: 'Validate - lint md-files'", validateMarkdownJob );
const contractStep = findLine( "      - name: 'Validate - doc-link contract'", validateMarkdownJob );
const externalLinksStep = findLine( "      - name: 'Validate - external doc links'", validateMarkdownJob );
const docsBuildStep = findLine( "      - name: 'Validate - lint docs-files'", validateMarkdownJob );
assert.ok( markdownLintStep < contractStep && contractStep < externalLinksStep && externalLinksStep < docsBuildStep, 'doc-link validation must run between Markdown lint and the docs build' );
requireLines( block( contractStep ), [
	'id: doc-link-contract',
	"if: ${{ always() && steps.doc-link-changes.outputs.needs-doc-link-contract-validation == 'true' }}",
	'run: bash .github/workflows/scripts/check-doc-links.test.sh',
], 'doc-link contract step' );
const externalLinkLines = block( externalLinksStep );
requireLines( externalLinkLines, [
	"if: ${{ always() && steps.doc-link-changes.outputs.needs-doc-link-validation == 'true' && ( steps.doc-link-contract.outcome == 'success' || steps.doc-link-contract.outcome == 'skipped' ) }}",
	'timeout-minutes: 2',
	'DOC_LINK_FILES_JSON: ${{ steps.doc-link-changes.outputs.needs-doc-link-validation_files }}',
	'run: node .github/workflows/scripts/check-doc-links.js',
], 'external doc-link step' );
assert.equal( externalLinkLines.filter( ( line ) => line.includes( '${{' ) ).every( ( line ) => line.startsWith( 'if:' ) || line.startsWith( 'DOC_LINK_FILES_JSON:' ) ), true, 'expressions must not reach the live checker command' );

const config = JSON.parse( fs.readFileSync( process.env.CONFIG_FILE, 'utf8' ) );
assert.deepEqual( Object.keys( config ).sort(), [ 'aliveStatusCodes', 'fallbackRetryDelay', 'ignorePatterns', 'retryCount', 'retryOn429', 'timeout' ], 'config contains unexpected behavior keys' );
assert.equal( config.ignorePatterns.length, 3, 'expected separate delegated-link, loopback, and npm package patterns' );
const delegatedPattern = new RegExp( config.ignorePatterns[ 0 ].pattern );
const loopbackPattern = new RegExp( config.ignorePatterns[ 1 ].pattern );
const npmPackagePattern = new RegExp( config.ignorePatterns[ 2 ].pattern );
for ( const link of [ 'docs/guide.md', '/docs/guide.md', '#configuration', 'mailto:docs@example.com' ] ) {
	assert.equal( delegatedPattern.test( link ), true, `delegated link should be ignored: ${ link }` );
	assert.equal( loopbackPattern.test( link ), false, `delegated link should not rely on loopback handling: ${ link }` );
}
for ( const link of [ 'http://example.com/docs', 'HTTPS://example.com/docs' ] ) {
	assert.equal( delegatedPattern.test( link ), false, `external HTTP(S) link should be checked: ${ link }` );
	assert.equal( loopbackPattern.test( link ), false, `external HTTP(S) link should not match loopback: ${ link }` );
	assert.equal( npmPackagePattern.test( link ), false, `external HTTP(S) link should not match npm packages: ${ link }` );
}
for ( const link of [ 'http://localhost', 'https://LOCALHOST:8080/docs', 'http://127.0.0.1:3000/docs?view=full#section', 'https://[::1]/docs' ] ) {
	assert.equal( delegatedPattern.test( link ), false, `loopback HTTP(S) link should reach loopback handling: ${ link }` );
	assert.equal( loopbackPattern.test( link ), true, `loopback HTTP(S) link should be ignored: ${ link }` );
}
for ( const link of [ 'https://www.npmjs.com/package/@wordpress/env', 'HTTP://NPMJS.COM/package/@woocommerce/data' ] ) {
	assert.equal( npmPackagePattern.test( link ), true, `npm package page should be ignored: ${ link }` );
}
for ( const link of [ 'https://www.npmjs.com/search?q=woocommerce', 'https://example.com/package/@wordpress/env' ] ) {
	assert.equal( npmPackagePattern.test( link ), false, `non-package URL should not match the npm exception: ${ link }` );
}
assert.deepEqual( [ config.retryOn429, config.retryCount, config.fallbackRetryDelay, config.timeout ], [ true, 1, '5s', '5s' ] );
assert.deepEqual( config.aliveStatusCodes, [ 200, 204, 206 ] );
assert.equal( config.aliveStatusCodes.includes( 403 ), false );
const forbiddenConfigurationKeys = [];
const collectForbiddenKeys = ( value, path = [] ) => {
	if ( ! value || typeof value !== 'object' ) {
		return;
	}
	for ( const [ key, nestedValue ] of Object.entries( value ) ) {
		if ( /auth|header/i.test( key ) ) {
			forbiddenConfigurationKeys.push( [ ...path, key ].join( '.' ) );
		}
		collectForbiddenKeys( nestedValue, [ ...path, key ] );
	}
};
collectForbiddenKeys( config );
assert.deepEqual( forbiddenConfigurationKeys, [], 'authentication and header configuration must be absent' );
NODE

printf 'PASS: link-check workflow, helper, and configuration contract\n'
