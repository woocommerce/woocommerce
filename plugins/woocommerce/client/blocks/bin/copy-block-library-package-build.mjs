#!/usr/bin/env node

/**
 * Syncs the `packages/block-library` build into WooCommerce's runtime assets.
 *
 * `wp-build` writes package output into two places:
 * - `plugins/woocommerce/client/blocks/build` for generated PHP registration
 *   and bundled browser scripts.
 * - `plugins/woocommerce/client/blocks/packages/block-library/build` for the
 *   package's CommonJS build and copied block metadata.
 *
 * WooCommerce loads block assets from `plugins/woocommerce/assets/client/blocks`,
 * so this script copies those generated files into that final folder. In watch
 * mode it polls the package source content, runs `wp-build` when source changes,
 * and then copies the fresh output.
 */

/**
 * External dependencies
 */
import { cp, mkdir, readFile, readdir, stat } from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const currentDir = path.dirname( fileURLToPath( import.meta.url ) );
const blocksRoot = path.resolve( currentDir, '..' );
const packageRoot = path.join( blocksRoot, 'packages/block-library' );
const packageBuildDir = path.join( packageRoot, 'build' );
const packageSourceDir = path.join( packageRoot, 'src' );
const generatedBuildDir = path.join( blocksRoot, 'build' );
const runtimeAssetsDir = path.resolve(
	blocksRoot,
	'../../assets/client/blocks'
);
const builtPluginAssetsDir = path.resolve(
	blocksRoot,
	'../../build/woocommerce/assets/client/blocks'
);
const isWatchMode = process.argv.includes( '--watch' );
const pollIntervalMs = 500;
let isBuilding = false;
let needsBuild = false;

async function exists( filePath ) {
	try {
		await stat( filePath );
		return true;
	} catch ( error ) {
		if ( error.code === 'ENOENT' ) {
			return false;
		}
		throw error;
	}
}

async function isDirectoryOrPhpFile( filePath ) {
	return (
		( await stat( filePath ) ).isDirectory() || filePath.endsWith( '.php' )
	);
}

async function getSourceFiles( filePath ) {
	if ( ! ( await exists( filePath ) ) ) {
		return [];
	}

	const fileStat = await stat( filePath );

	if ( fileStat.isFile() ) {
		return [ filePath ];
	}

	const entries = await readdir( filePath, { withFileTypes: true } );
	const nestedFiles = await Promise.all(
		entries.map( ( entry ) =>
			getSourceFiles( path.join( filePath, entry.name ) )
		)
	);

	return nestedFiles.flat();
}

async function getSourceSignature() {
	// Hash file contents instead of mtimes so build/copy timestamp changes do
	// not retrigger watch mode.
	const files = (
		await Promise.all(
			[ packageSourceDir, path.join( packageRoot, 'package.json' ) ].map(
				getSourceFiles
			)
		)
	 )
		.flat()
		.sort();
	const hash = createHash( 'sha256' );

	for ( const filePath of files ) {
		hash.update( path.relative( packageRoot, filePath ) );
		hash.update( '\0' );
		hash.update( await readFile( filePath ) );
		hash.update( '\0' );
	}

	return hash.digest( 'hex' );
}

async function copyBuildOutput( targetDir ) {
	await mkdir( targetDir, { recursive: true } );
	await cp( generatedBuildDir, targetDir, { recursive: true, force: true } );
	await cp( packageBuildDir, targetDir, { recursive: true, force: true } );
	await cp( packageSourceDir, targetDir, {
		recursive: true,
		force: true,
		filter: isDirectoryOrPhpFile,
	} );
}

async function copyPackageBuildOutput() {
	if ( ! ( await exists( packageBuildDir ) ) ) {
		throw new Error(
			'Expected packages/block-library/build to exist. Run wp-build before copying block-library package files.'
		);
	}

	await copyBuildOutput( runtimeAssetsDir );

	if ( await exists( path.dirname( builtPluginAssetsDir ) ) ) {
		await copyBuildOutput( builtPluginAssetsDir );
	}

	// eslint-disable-next-line no-console
	console.log( 'Copied block-library package build output.' );
}

async function runWpBuild() {
	await new Promise( ( resolve, reject ) => {
		const wpBuild = spawn( 'wp-build', [], {
			cwd: blocksRoot,
			stdio: 'inherit',
		} );

		wpBuild.once( 'exit', ( code ) => {
			if ( code === 0 ) {
				resolve();
				return;
			}

			reject( new Error( `wp-build exited with code ${ code }.` ) );
		} );
		wpBuild.once( 'error', reject );
	} );
}

async function buildAndCopy() {
	if ( isBuilding ) {
		needsBuild = true;
		return;
	}

	isBuilding = true;

	try {
		do {
			needsBuild = false;
			await runWpBuild();
			await copyPackageBuildOutput();
		} while ( needsBuild );
	} finally {
		isBuilding = false;
	}
}

if ( isWatchMode ) {
	let sourceSignature = await getSourceSignature();
	let poll;
	const shutdown = () => {
		clearInterval( poll );
		process.exit( 0 );
	};

	process.once( 'SIGINT', shutdown );
	process.once( 'SIGTERM', shutdown );

	await buildAndCopy();
	sourceSignature = await getSourceSignature();

	poll = setInterval( () => {
		if ( isBuilding ) {
			return;
		}

		getSourceSignature()
			.then( ( nextSourceSignature ) => {
				if ( nextSourceSignature !== sourceSignature ) {
					sourceSignature = nextSourceSignature;
					return buildAndCopy().then( async () => {
						sourceSignature = await getSourceSignature();
					} );
				}

				return undefined;
			} )
			.catch( ( error ) => {
				// eslint-disable-next-line no-console
				console.error( error );
			} );
	}, pollIntervalMs );
} else {
	await copyPackageBuildOutput();
}
