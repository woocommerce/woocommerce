import { build, context } from 'esbuild';
import { glob } from 'glob';
import { copyFile, mkdir, rm } from 'node:fs/promises';
import { dirname, join, relative } from 'node:path';
import chokidar from 'chokidar';

const watch = process.argv.includes( '--watch' );
const format = process.argv.includes( '--cjs' ) ? 'cjs' : 'esm';
const outdir = format === 'cjs' ? 'build' : 'build-module';

const ENTRY_GLOB = 'src/**/*.{ts,tsx,js,jsx}';
const ENTRY_IGNORE = [ '**/test/**', '**/stories/**', '**/*.d.ts' ];
const BLOCK_JSON_GLOB = 'src/**/block.json';

async function resolveEntryPoints() {
	return glob( ENTRY_GLOB, { ignore: ENTRY_IGNORE } );
}

// block.json files are runtime-imported by src/blocks/**/index.ts. esbuild in
// transpile mode preserves the `import './block.json'` statements as-is, so
// the .json files must sit next to the emitted .js for those imports to
// resolve.
async function copyBlockJson() {
	for ( const src of await glob( BLOCK_JSON_GLOB ) ) {
		const dest = join( outdir, relative( 'src', src ) );
		await mkdir( dirname( dest ), { recursive: true } );
		await copyFile( src, dest );
	}
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

await rm( outdir, { recursive: true, force: true } );

if ( watch ) {
	const startupT0 = Date.now();
	let entryPoints = await resolveEntryPoints();
	let ctx = await context( makeOptions( entryPoints ) );
	const initial = await ctx.rebuild();
	await copyBlockJson();
	console.log( `[watch] ready in ${ Date.now() - startupT0 }ms — ${ entryPoints.length } entry point(s)${ summarize( initial ) }` );

	let pending;
	const pendingChanges = new Set();
	const restart = ( path, kind ) => {
		pendingChanges.add( `${ path } (${ kind })` );
		clearTimeout( pending );
		pending = setTimeout( async () => {
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
			await copyBlockJson();
			console.log( `[watch] rebuilt in ${ Date.now() - t0 }ms — ${ entryPoints.length } entry point(s)${ summarize( result ) }` );
		}, 200 );
	};

	chokidar
		.watch( [ ENTRY_GLOB, BLOCK_JSON_GLOB ], { ignored: ENTRY_IGNORE, ignoreInitial: true } )
		.on( 'add', ( path ) => restart( path, 'added' ) )
		.on( 'unlink', ( path ) => restart( path, 'deleted' ) )
		.on( 'change', async ( path ) => {
			const t0 = Date.now();
			const result = await ctx.rebuild().catch( ( error ) => ( { errors: [ error ], warnings: [] } ) );
			if ( path.endsWith( '.json' ) ) await copyBlockJson();
			console.log( `[watch] rebuilt ${ path } in ${ Date.now() - t0 }ms${ summarize( result ) }` );
		} );
} else {
	const entryPoints = await resolveEntryPoints();
	const t0 = Date.now();
	console.log( `[build] ${ entryPoints.length } entry point(s)...` );
	const result = await build( makeOptions( entryPoints ) );
	await copyBlockJson();
	console.log( `[build] done in ${ Date.now() - t0 }ms${ summarize( result ) }` );
}
