#!/usr/bin/env node

/**
 * External dependencies
 */
import { copyFile, mkdir, readdir, rm, stat } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const currentDir = path.dirname( fileURLToPath( import.meta.url ) );
const blocksRoot = path.resolve( currentDir, '..' );
const packageRoot = path.join( blocksRoot, 'packages/block-library' );
const packageBuildDir = path.join( packageRoot, 'build' );
const packageSourceDir = path.join( packageRoot, 'src' );
const scriptsBuildDir = path.join(
	blocksRoot,
	'build/scripts/block-library'
);
const blockMetadataBuildDir = path.join( blocksRoot, 'build' );
const generatedScriptsDir = path.join( blocksRoot, 'build/scripts' );
const runtimeAssetsDir = path.resolve( blocksRoot, '../../assets/client/blocks' );
const runtimeBlocksManifestPath = path.join( runtimeAssetsDir, 'blocks-json.php' );
const builtPluginDir = path.resolve( blocksRoot, '../../build/woocommerce' );
const builtPluginAssetsDir = path.resolve(
	builtPluginDir,
	'assets/client/blocks'
);
const generatedBuildFiles = [
	'build.php',
	'constants.php',
	'modules.php',
	'pages.php',
	'routes.php',
	'scripts.php',
	'styles.php',
	'widgets.php',
];
const generatedBuildDirectories = [
	'modules',
	'pages',
	'routes',
	'scripts',
	'styles',
	'widgets',
];

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

async function getFiles( directory ) {
	if ( ! ( await exists( directory ) ) ) {
		return [];
	}

	const files = [];
	const entries = await readdir( directory, { withFileTypes: true } );

	for ( const entry of entries ) {
		const filePath = path.join( directory, entry.name );

		if ( entry.isDirectory() ) {
			files.push( ...( await getFiles( filePath ) ) );
			continue;
		}

		if ( entry.isFile() ) {
			files.push( filePath );
		}
	}

	return files;
}

async function copyMatchingFiles( sourceRoot, targetRoot, shouldCopy ) {
	const files = await getFiles( sourceRoot );

	await Promise.all(
		files.filter( shouldCopy ).map( async ( sourcePath ) => {
			const targetPath = path.join(
				targetRoot,
				path.relative( sourceRoot, sourcePath )
			);

			await mkdir( path.dirname( targetPath ), { recursive: true } );
			await copyFile( sourcePath, targetPath );
		} )
	);
}

async function copyDirectory( sourceRoot, targetRoot ) {
	const files = await getFiles( sourceRoot );

	await rm( targetRoot, { recursive: true, force: true } );

	await Promise.all(
		files.map( async ( sourcePath ) => {
			const targetPath = path.join(
				targetRoot,
				path.relative( sourceRoot, sourcePath )
			);

			await mkdir( path.dirname( targetPath ), { recursive: true } );
			await copyFile( sourcePath, targetPath );
		} )
	);
}

async function copyGeneratedBuildRegistration( sourceRoot, targetRoot ) {
	await mkdir( targetRoot, { recursive: true } );
	await rm( path.join( targetRoot, 'registry.php' ), { force: true } );
	await rm( path.join( targetRoot, 'block-library' ), {
		recursive: true,
		force: true,
	} );

	await Promise.all(
		generatedBuildFiles.map( async ( fileName ) => {
			const sourcePath = path.join( sourceRoot, fileName );

			if ( ! ( await exists( sourcePath ) ) ) {
				return;
			}

			await copyFile( sourcePath, path.join( targetRoot, fileName ) );
		} )
	);

	await Promise.all(
		generatedBuildDirectories.map( async ( directoryName ) => {
			const sourcePath = path.join( sourceRoot, directoryName );

			if ( ! ( await exists( sourcePath ) ) ) {
				return;
			}

			await copyDirectory(
				sourcePath,
				path.join( targetRoot, directoryName )
			);
		} )
	);
}

async function cleanCopiedMetadataFiles( directory ) {
	const files = await getFiles( directory );

	await Promise.all(
		files.filter( isBlockMetadataOrRenderFile ).map( ( filePath ) =>
			rm( filePath )
		)
	);
}

function isBlockMetadataOrRenderFile( filePath ) {
	const basename = path.basename( filePath );

	return (
		basename === 'block.json' ||
		( path.extname( filePath ) === '.php' &&
			! basename.endsWith( '.asset.php' ) )
	);
}

if ( ! ( await exists( packageBuildDir ) ) ) {
	throw new Error(
		'Expected packages/block-library/build to exist. Run wp-build before copying block-library package files.'
	);
}

await copyMatchingFiles(
	packageSourceDir,
	packageBuildDir,
	( filePath ) => path.extname( filePath ) === '.php'
);
await copyMatchingFiles( packageBuildDir, blockMetadataBuildDir, () => true );
await cleanCopiedMetadataFiles( scriptsBuildDir );
await copyMatchingFiles(
	packageBuildDir,
	scriptsBuildDir,
	isBlockMetadataOrRenderFile
);
await copyGeneratedBuildRegistration( blockMetadataBuildDir, runtimeAssetsDir );
await copyMatchingFiles( packageBuildDir, runtimeAssetsDir, () => true );

if ( await exists( builtPluginDir ) ) {
	await copyGeneratedBuildRegistration(
		blockMetadataBuildDir,
		builtPluginAssetsDir
	);
	await copyMatchingFiles( packageBuildDir, builtPluginAssetsDir, () => true );

	if ( await exists( runtimeBlocksManifestPath ) ) {
		await copyFile(
			runtimeBlocksManifestPath,
			path.join( builtPluginAssetsDir, 'blocks-json.php' )
		);
	}
}

console.log(
	'Copied block-library package metadata and generated scripts.'
);
