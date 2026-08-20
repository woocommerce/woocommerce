/**
 * External dependencies
 */
import fs from 'node:fs';
import path from 'node:path';

/*
 * Keeps the plugin-installing wp-env E2E variant configs in sync with the base
 * `.wp-env.e2e.json`. Each variant is a full standalone config (its own wp-env
 * instance and baseline) that must be identical to the base except for one extra
 * plugin appended to `plugins[]`. Run with `--fix` to (re)generate them.
 *
 * Run from the plugin root. Positional args (e.g. a `<baseRef>` passed by the
 * `lint:changes:branch` family) are ignored - only the `--fix` flag matters.
 */
const ROOT = process.cwd();
const BASE_CONFIG = '.wp-env.e2e.json';

// The extra plugin(s) each variant appends to the base plugin list.
const VARIANTS = {
	'.wp-env.e2e.gutenberg-stable.json': [
		'https://downloads.wordpress.org/plugin/gutenberg.zip',
	],
	'.wp-env.e2e.gutenberg-nightly.json': [
		'https://github.com/bph/gutenberg/releases/latest/download/gutenberg.zip',
	],
	'.wp-env.e2e.default-object-cache.json': [
		'https://downloads.wordpress.org/plugin/sqlite-object-cache.zip',
	],
};

const fix = process.argv.includes( '--fix' );

const base = JSON.parse(
	fs.readFileSync( path.join( ROOT, BASE_CONFIG ), 'utf8' )
);

const render = ( extraPlugins ) =>
	JSON.stringify(
		{ ...base, plugins: [ ...base.plugins, ...extraPlugins ] },
		null,
		'\t'
	) + '\n';

let failed = false;

for ( const [ file, extraPlugins ] of Object.entries( VARIANTS ) ) {
	const expected = render( extraPlugins );
	const filePath = path.join( ROOT, file );

	if ( fix ) {
		fs.writeFileSync( filePath, expected );
		continue;
	}

	const actual = fs.existsSync( filePath )
		? fs.readFileSync( filePath, 'utf8' )
		: null;
	if ( actual !== expected ) {
		console.error(
			`${ file } is out of sync with ${ BASE_CONFIG }. Run: node tests/e2e/bin/check-wp-env-variants.mjs --fix`
		);
		failed = true;
	}
}

// Guard against a variant config that nobody registered here: it would drift
// silently (this checker never regenerates it) and CI would start it via a
// `--config` the drift check ignores.
const stray = fs
	.readdirSync( ROOT )
	.filter(
		( file ) =>
			/^\.wp-env\.e2e\..+\.json$/.test( file ) &&
			! file.endsWith( '.override.json' ) &&
			! ( file in VARIANTS )
	);
for ( const file of stray ) {
	console.error(
		`${ file } looks like a wp-env E2E variant but is not registered in tests/e2e/bin/check-wp-env-variants.mjs. Add it to VARIANTS or remove it.`
	);
	failed = true;
}

process.exit( failed ? 1 : 0 );
