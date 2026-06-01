import { build, context } from 'esbuild';
import { glob } from 'glob';
import { rm } from 'node:fs/promises';
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
				console.log( `[watch] rebuilt ${ path } in ${ Date.now() - t0 }ms${ summarize( result ) }` );
			}
		} );
} else {
	const entryPoints = await resolveEntryPoints();
	const t0 = Date.now();
	console.log( `[build] ${ entryPoints.length } entry point(s)...` );
	const result = await build( makeOptions( entryPoints ) );
	console.log( `[build] done in ${ Date.now() - t0 }ms${ summarize( result ) }` );
}
