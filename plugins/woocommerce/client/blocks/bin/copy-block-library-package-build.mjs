#!/usr/bin/env node

/**
 * External dependencies
 */
import {
	copyFile,
	mkdir,
	readdir,
	rm,
	stat,
	writeFile,
} from 'node:fs/promises';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const currentDir = path.dirname( fileURLToPath( import.meta.url ) );
const requireFromBlocks = createRequire(
	path.join( currentDir, '../package.json' )
);
const blocksRoot = path.resolve( currentDir, '..' );
const packageRoot = path.join( blocksRoot, 'packages/block-library' );
const packageBuildDir = path.join( packageRoot, 'build' );
const packageSourceDir = path.join( packageRoot, 'src' );
const scriptsBuildDir = path.join( blocksRoot, 'build/scripts/block-library' );
const blockMetadataBuildDir = path.join( blocksRoot, 'build' );
const generatedScriptsDir = path.join( blocksRoot, 'build/scripts' );
const runtimeAssetsDir = path.resolve(
	blocksRoot,
	'../../assets/client/blocks'
);
const runtimeBlocksManifestPath = path.join(
	runtimeAssetsDir,
	'blocks-json.php'
);
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
const wordpressIconsPackage = requireFromBlocks(
	'@wordpress/icons/package.json'
);
const wordpressIconsFallbackDependencies = [
	'react-jsx-runtime',
	'wp-primitives',
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

function getWordpressIconsFallbackScript( minified = false ) {
	const script = `( function ( wp, ReactJSXRuntime ) {
\tif ( ! wp || wp.icons ) {
\t\treturn;
\t}

\tconst primitives = wp.primitives || {};
\tconst SVG = primitives.SVG;
\tconst Path = primitives.Path;
\tconst jsx = ReactJSXRuntime && ReactJSXRuntime.jsx;

\tif ( ! SVG || ! Path || ! jsx ) {
\t\treturn;
\t}

\twp.icons = {
\t\theading: jsx( SVG, {
\t\t\txmlns: 'http://www.w3.org/2000/svg',
\t\t\tviewBox: '0 0 24 24',
\t\t\tchildren: jsx( Path, {
\t\t\t\td: 'M6 5V18.5911L12 13.8473L18 18.5911V5H6Z',
\t\t\t} ),
\t\t} ),
\t};
} )( ( window.wp = window.wp || {} ), window.ReactJSXRuntime );
`;

	if ( ! minified ) {
		return script;
	}

	return "(function(wp,ReactJSXRuntime){if(!wp||wp.icons){return;}const primitives=wp.primitives||{};const SVG=primitives.SVG;const Path=primitives.Path;const jsx=ReactJSXRuntime&&ReactJSXRuntime.jsx;if(!SVG||!Path||!jsx){return;}wp.icons={heading:jsx(SVG,{xmlns:'http://www.w3.org/2000/svg',viewBox:'0 0 24 24',children:jsx(Path,{d:'M6 5V18.5911L12 13.8473L18 18.5911V5H6Z'})})};})((window.wp=window.wp||{}),window.ReactJSXRuntime);\n";
}

async function writeWordpressIconsFallback() {
	const fallbackDir = path.join( generatedScriptsDir, 'wp-icons' );
	const assetPhp = `<?php
/**
 * WordPress Icons fallback asset file.
 *
 * @package woocommerce_blocks_block_library
 */

return array(
\t'dependencies' => array( '${ wordpressIconsFallbackDependencies.join(
		"', '"
	) }' ),
\t'version'      => '${ wordpressIconsPackage.version }',
);
`;

	await mkdir( fallbackDir, { recursive: true } );
	await writeFile(
		path.join( fallbackDir, 'index.js' ),
		getWordpressIconsFallbackScript()
	);
	await writeFile(
		path.join( fallbackDir, 'index.min.js' ),
		getWordpressIconsFallbackScript( true )
	);
	await writeFile(
		path.join( fallbackDir, 'index.min.asset.php' ),
		assetPhp
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

async function cleanCopiedMetadataFiles( directory ) {
	const files = await getFiles( directory );

	await Promise.all(
		files
			.filter( isBlockMetadataOrRenderFile )
			.map( ( filePath ) => rm( filePath ) )
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
await writeWordpressIconsFallback();
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
	await copyMatchingFiles(
		packageBuildDir,
		builtPluginAssetsDir,
		() => true
	);

	if ( await exists( runtimeBlocksManifestPath ) ) {
		await copyFile(
			runtimeBlocksManifestPath,
			path.join( builtPluginAssetsDir, 'blocks-json.php' )
		);
	}
}

// eslint-disable-next-line no-console
console.log( 'Copied block-library package metadata and generated scripts.' );
