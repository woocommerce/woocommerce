/**
 * External dependencies
 */
import { build, context } from 'esbuild';
import type { BuildResult } from 'esbuild';
import { glob } from 'glob';
import { rm } from 'node:fs/promises';
import chokidar from 'chokidar';

/**
 * Internal dependencies
 */
import { parseBuildArgs } from './args.js';
import {
	DEFAULT_IGNORE,
	prepareEsbuildOptions,
	type BuildOptions,
} from './options.js';
import { copyAssets } from './assets.js';
import { log, setDebugEnabled } from '../utils/logger.js';

export type { BuildOptions } from './options.js';
export { parseBuildArgs } from './args.js';

const RESTART_DEBOUNCE_MS = 200;

async function resolveEntryPoints(
	entryPoints: string | string[],
	ignore: readonly string[]
): Promise< string[] > {
	const patterns = Array.isArray( entryPoints )
		? entryPoints
		: [ entryPoints ];
	const results = await Promise.all(
		patterns.map( ( pattern ) =>
			glob( pattern, { ignore: [ ...ignore ] } )
		)
	);
	return Array.from( new Set( results.flat() ) ).sort();
}

function summarize( result: BuildResult ): string {
	const parts: string[] = [];
	if ( result.errors.length )
		parts.push( `${ result.errors.length } error(s)` );
	if ( result.warnings.length )
		parts.push( `${ result.warnings.length } warning(s)` );
	return parts.length ? ` — ${ parts.join( ', ' ) }` : '';
}

function errorMessage( error: unknown ): string {
	return error && typeof error === 'object' && 'message' in error
		? String( ( error as { message: unknown } ).message )
		: String( error );
}

export async function buildPackage( options: BuildOptions ): Promise< void > {
	const format = options.format ?? 'esmodules';
	const ignore = [ ...DEFAULT_IGNORE, ...( options.ignore ?? [] ) ];
	const outdir = format === 'commonjs' ? 'build' : 'build-module';

	await rm( outdir, { recursive: true, force: true } );
	const entryPoints = await resolveEntryPoints( options.entryPoints, ignore );

	log.debug( 'build', `format: ${ format }, outdir: ${ outdir }` );
	for ( const entry of entryPoints )
		log.debug( 'build', `entry: ${ entry }` );

	const t0 = Date.now();
	log.info( 'build', `${ entryPoints.length } entry point(s)...` );
	const result = await build(
		prepareEsbuildOptions( format, entryPoints, options.esbuild )
	);
	if ( options.assets?.length ) await copyAssets( options.assets, outdir );
	log.info( 'ok', `done in ${ Date.now() - t0 }ms${ summarize( result ) }` );
}

export async function watchPackage( options: BuildOptions ): Promise< void > {
	const format = options.format ?? 'esmodules';
	const ignore = [ ...DEFAULT_IGNORE, ...( options.ignore ?? [] ) ];
	const assets = options.assets ?? [];
	const outdir = format === 'commonjs' ? 'build' : 'build-module';
	const watchedPatterns = [
		...( Array.isArray( options.entryPoints )
			? options.entryPoints
			: [ options.entryPoints ] ),
		...assets,
	];

	const startupT0 = Date.now();
	await rm( outdir, { recursive: true, force: true } );

	let entryPoints = await resolveEntryPoints( options.entryPoints, ignore );
	let ctx = await context(
		prepareEsbuildOptions( format, entryPoints, options.esbuild )
	);

	log.debug( 'watch', `format: ${ format }, outdir: ${ outdir }` );
	log.debug( 'watch', `watching: ${ watchedPatterns.join( ', ' ) }` );
	for ( const entry of entryPoints )
		log.debug( 'watch', `entry: ${ entry }` );

	try {
		const initial = await ctx.rebuild();
		if ( assets.length ) await copyAssets( assets, outdir );
		log.info(
			'watch',
			`ready in ${ Date.now() - startupT0 }ms — ${
				entryPoints.length
			} entry point(s)${ summarize( initial ) }`
		);
	} catch ( error ) {
		log.error(
			'watch',
			`startup build failed: ${ errorMessage( error ) }`
		);
	}

	// esbuild's own watcher polls the filesystem, which can miss or delay
	// changes (especially edits to files added after context creation).
	// chokidar uses OS-level events and drives rebuilds directly: changes
	// call ctx.rebuild() (preserves the AST cache), add/unlink trigger a
	// debounced context restart (entry list changed).
	let pending: NodeJS.Timeout | undefined;
	const pendingChanges = new Set< string >();

	const restart = ( path: string, kind: string ): void => {
		pendingChanges.add( `${ path } (${ kind })` );
		log.debug( 'watch', `${ kind }: ${ path }` );
		if ( pending ) clearTimeout( pending );
		pending = setTimeout( () => {
			void ( async () => {
				const changes = [ ...pendingChanges ];
				pendingChanges.clear();
				const preview = changes.slice( 0, 3 ).join( ', ' );
				const suffix =
					changes.length > 3 ? `, +${ changes.length - 3 } more` : '';
				log.info( 'watch', `restarting (${ preview }${ suffix })` );
				const t0 = Date.now();
				try {
					await ctx.dispose();
					await rm( outdir, { recursive: true, force: true } );
					entryPoints = await resolveEntryPoints(
						options.entryPoints,
						ignore
					);
					ctx = await context(
						prepareEsbuildOptions(
							format,
							entryPoints,
							options.esbuild
						)
					);
					const result = await ctx.rebuild();
					if ( assets.length ) await copyAssets( assets, outdir );
					log.info(
						'ok',
						`rebuilt in ${ Date.now() - t0 }ms — ${
							entryPoints.length
						} entry point(s)${ summarize( result ) }`
					);
				} catch ( error ) {
					log.error(
						'watch',
						`restart failed: ${ errorMessage( error ) }`
					);
				}
			} )();
		}, RESTART_DEBOUNCE_MS );
	};

	const watcher = chokidar
		.watch( watchedPatterns, {
			ignored: [ ...ignore ],
			ignoreInitial: true,
			awaitWriteFinish: { stabilityThreshold: 50, pollInterval: 20 },
		} )
		.on( 'add', ( path ) => restart( path, 'added' ) )
		.on( 'unlink', ( path ) => restart( path, 'deleted' ) )
		.on( 'change', ( path ) => {
			void ( async () => {
				log.debug( 'watch', `changed: ${ path }` );
				const t0 = Date.now();
				try {
					const result = await ctx.rebuild();
					if ( assets.length && path.endsWith( '.json' ) ) {
						await copyAssets( assets, outdir );
					}
					log.info(
						'ok',
						`rebuilt ${ path } in ${
							Date.now() - t0
						}ms${ summarize( result ) }`
					);
				} catch ( error ) {
					log.error(
						'watch',
						`rebuild ${ path } failed: ${ errorMessage( error ) }`
					);
				}
			} )();
		} );

	const shutdown = async (): Promise< void > => {
		log.info( 'watch', 'shutting down' );
		await watcher.close();
		await ctx.dispose();
		process.exit( 0 );
	};
	process.once( 'SIGINT', () => void shutdown() );
	process.once( 'SIGTERM', () => void shutdown() );
}

export async function runPackageBuilder(
	options: BuildOptions
): Promise< void > {
	const args = parseBuildArgs();
	setDebugEnabled( args.debug );
	const merged: BuildOptions = {
		...options,
		format: options.format ?? args.format,
	};
	if ( args.watch ) {
		await watchPackage( merged );
	} else {
		await buildPackage( merged );
	}
}
