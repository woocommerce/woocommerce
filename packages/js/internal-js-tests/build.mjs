import { build, context } from 'esbuild';
import { glob } from 'glob';
import { basename } from 'node:path';
import { readFile, rm, unlink } from 'node:fs/promises';
import chokidar from 'chokidar';

const watch = process.argv.includes( '--watch' );
const format = process.argv.includes( '--cjs' ) ? 'cjs' : 'esm';
const outdir = format === 'cjs' ? 'build' : 'build-module';

const ENTRY_GLOB = 'src/**/*.{ts,tsx,js,jsx}';
const ENTRY_IGNORE = [
	'**/test/**',
	'**/stories/**',
	'**/*.test.{ts,tsx,js,jsx}',
	'**/*.d.ts',
	'src/setup-*.js',
	'src/mocks/**',
];

async function resolveEntryPoints() {
	return glob( ENTRY_GLOB, { ignore: ENTRY_IGNORE } );
}

// Type-only TypeScript sources (`export type Foo = ...`) emit byte-identical
// "empty stub" output: ESM is 0 bytes, CJS is the __toCommonJS boilerplate
// closing over an empty exports object whose varname is derived from the
// filename. We reconstruct the exact expected stub from the filename and
// compare byte-for-byte — anything else (real code, barrel re-exports via
// `__reExport`, single-export files) is left alone.
const CJS_STUB_PREAMBLE =
	'"use strict";\n' +
	'var __defProp = Object.defineProperty;\n' +
	'var __getOwnPropDesc = Object.getOwnPropertyDescriptor;\n' +
	'var __getOwnPropNames = Object.getOwnPropertyNames;\n' +
	'var __hasOwnProp = Object.prototype.hasOwnProperty;\n' +
	'var __copyProps = (to, from, except, desc) => {\n' +
	'  if (from && typeof from === "object" || typeof from === "function") {\n' +
	'    for (let key of __getOwnPropNames(from))\n' +
	'      if (!__hasOwnProp.call(to, key) && key !== except)\n' +
	'        __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });\n' +
	'  }\n' +
	'  return to;\n' +
	'};\n' +
	'var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);\n';

function expectedCjsStub( file ) {
	const stem = basename( file ).replace( /\.[^.]+$/, '' );
	const varname = stem.replace( /[^A-Za-z0-9_$]/g, '_' ) + '_exports';
	return (
		CJS_STUB_PREAMBLE +
		`var ${ varname } = {};\n` +
		`module.exports = __toCommonJS(${ varname });\n`
	);
}

async function pruneEmptyStubs() {
	const files = await glob( `${ outdir }/**/*.js` );
	let removed = 0;
	let bytes = 0;
	for ( const file of files ) {
		const text = await readFile( file, 'utf8' );
		if ( format === 'esm' ) {
			if ( text.length !== 0 ) continue;
		} else if ( text !== expectedCjsStub( file ) ) {
			continue;
		}
		bytes += text.length;
		await unlink( file );
		removed++;
	}
	return { removed, bytes };
}

function makeOptions( entryPoints ) {
	return {
		entryPoints,
		outdir,
		outbase: 'src',
		bundle: false,
		format,
		platform: 'neutral',
		target: 'esnext',
		loader: { '.js': 'jsx', '.jsx': 'jsx', '.ts': 'ts', '.tsx': 'tsx' },
		jsx: 'transform',
		jsxFactory: 'createElement',
		jsxFragment: 'Fragment',
		logLevel: 'warning',
		sourcemap: false,
	};
}

function summarize( result ) {
	const errors = result.errors.length;
	const warnings = result.warnings.length;
	const parts = [];
	if ( errors ) parts.push( `${ errors } error(s)` );
	if ( warnings ) parts.push( `${ warnings } warning(s)` );
	return parts.length ? ` — ${ parts.join( ', ' ) }` : '';
}

// Wrap a watch-mode step so a single failure (disk error, build crash, etc.)
// doesn't take the watcher process down. Errors are surfaced; the loop survives.
async function safe( label, fn ) {
	try {
		return await fn();
	} catch ( error ) {
		console.error( `[watch] ${ label } failed:`, error?.message ?? error );
		return null;
	}
}

await rm( outdir, { recursive: true, force: true } );

if ( watch ) {
	const startupT0 = Date.now();
	let entryPoints = await resolveEntryPoints();
	let ctx = await context( makeOptions( entryPoints ) );
	const initial = await safe( 'startup build', () => ctx.rebuild() );
	await safe( 'prune stubs', pruneEmptyStubs );
	console.log( `[watch] ready in ${ Date.now() - startupT0 }ms — ${ entryPoints.length } entry point(s)${ initial ? summarize( initial ) : '' }` );

	// esbuild's own watcher polls the filesystem, which can miss or delay
	// changes (especially edits to files added after context creation).
	// chokidar uses OS-level events (fsevents/inotify) and drives rebuilds
	// directly: changes call ctx.rebuild() (preserves the AST cache),
	// add/unlink trigger a debounced context restart (entry list changed).
	let pending;
	const pendingChanges = new Set();
	const restart = ( path, kind ) => {
		pendingChanges.add( `${ path } (${ kind })` );
		clearTimeout( pending );
		pending = setTimeout( () => safe( 'restart', async () => {
			const changes = [ ...pendingChanges ];
			pendingChanges.clear();
			const preview = changes.slice( 0, 3 ).join( ', ' );
			const suffix = changes.length > 3 ? `, +${ changes.length - 3 } more` : '';
			console.log( `[watch] restarting (${ preview }${ suffix })` );
			const t0 = Date.now();
			await ctx.dispose();
			await rm( outdir, { recursive: true, force: true } );
			entryPoints = await resolveEntryPoints();
			ctx = await context( makeOptions( entryPoints ) );
			const result = await ctx.rebuild();
			await pruneEmptyStubs();
			console.log( `[watch] rebuilt in ${ Date.now() - t0 }ms — ${ entryPoints.length } entry point(s)${ summarize( result ) }` );
		} ), 200 );
	};

	chokidar
		.watch( ENTRY_GLOB, { ignored: ENTRY_IGNORE, ignoreInitial: true } )
		.on( 'add', ( path ) => restart( path, 'added' ) )
		.on( 'unlink', ( path ) => restart( path, 'deleted' ) )
		.on( 'change', async ( path ) => {
			const t0 = Date.now();
			const result = await safe( `rebuild ${ path }`, () => ctx.rebuild() );
			if ( result ) {
				await safe( 'prune stubs', pruneEmptyStubs );
				console.log( `[watch] rebuilt ${ path } in ${ Date.now() - t0 }ms${ summarize( result ) }` );
			}
		} );
} else {
	const entryPoints = await resolveEntryPoints();
	const t0 = Date.now();
	console.log( `[build] ${ entryPoints.length } entry point(s)...` );
	const result = await build( makeOptions( entryPoints ) );
	const pruned = await pruneEmptyStubs();
	const pruneNote = pruned.removed ? `, pruned ${ pruned.removed } empty stub(s) (${ pruned.bytes } bytes)` : '';
	console.log( `[build] done in ${ Date.now() - t0 }ms${ summarize( result ) }${ pruneNote }` );
}
