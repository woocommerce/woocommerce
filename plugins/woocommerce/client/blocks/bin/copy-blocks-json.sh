#!/bin/bash

# This script mimics the CopyWebpackPlugin behavior from the WooCommerce Blocks webpack configuration
# to make block.json files available for unit testing without requiring a full build.
# Ensure that the logic of this script is kept in sync with the logic of the CopyWebpackPlugin in the WooCommerce Blocks webpack configuration:
# https://github.com/woocommerce/woocommerce/blob/84d1da7be3cbd3d8f40b17ad58729f668fd82b6a/plugins/woocommerce/client/blocks/bin/webpack-configs.js#L229-L256

# Move to the project root
while [ ! -d "plugins/woocommerce" ] || [ ! -f "pnpm-workspace.yaml" ]; do
    if [ "$PWD" = "/" ]; then
        echo "Error: Could not find project root"
        exit 1
    fi
    cd ..
done

node <<'NODE'
const fs = require( 'node:fs' );
const path = require( 'node:path' );

const sourceDirectory = path.resolve( 'plugins/woocommerce/client/blocks/assets/js' );
const targetDirectory = path.resolve( 'plugins/woocommerce/assets/client/blocks' );
const genericBlocks = new Set( [
	'accordion-group',
	'accordion-header',
	'accordion-item',
	'accordion-panel',
] );

function findFiles( directory, fileName ) {
	return fs
		.readdirSync( directory, { withFileTypes: true } )
		.flatMap( ( entry ) => {
			const entryPath = path.join( directory, entry.name );

			if ( entry.isDirectory() ) {
				return findFiles( entryPath, fileName );
			}

			return entry.isFile() && entry.name === fileName
				? [ entryPath ]
				: [];
		} );
}

function removeEmptyDirectories( directory, rootDirectory ) {
	if ( ! fs.existsSync( directory ) ) {
		return;
	}

	for ( const entry of fs.readdirSync( directory, {
		withFileTypes: true,
	} ) ) {
		if ( entry.isDirectory() ) {
			removeEmptyDirectories(
				path.join( directory, entry.name ),
				rootDirectory
			);
		}
	}

	if (
		directory !== rootDirectory &&
		fs.readdirSync( directory ).length === 0
	) {
		fs.rmdirSync( directory );
	}
}

if ( ! fs.existsSync( sourceDirectory ) ) {
	throw new Error(
		`Block metadata source directory does not exist: ${ sourceDirectory }`
	);
}

const sourceManifests = findFiles( sourceDirectory, 'block.json' );
if ( sourceManifests.length === 0 ) {
	throw new Error(
		`No block metadata manifests found in ${ sourceDirectory }`
	);
}

fs.mkdirSync( targetDirectory, { recursive: true } );

for ( const targetManifest of findFiles( targetDirectory, 'block.json' ) ) {
	fs.unlinkSync( targetManifest );
}

const metadataCollection = path.join( targetDirectory, 'blocks-json.php' );
if ( fs.existsSync( metadataCollection ) ) {
	fs.unlinkSync( metadataCollection );
}

removeEmptyDirectories( targetDirectory, targetDirectory );

for ( const sourceManifest of sourceManifests ) {
	const metadata = JSON.parse(
		fs.readFileSync( sourceManifest, 'utf8' )
	);

	if ( typeof metadata.name !== 'string' ) {
		throw new Error( `Missing block name in ${ sourceManifest }` );
	}

	const blockName = metadata.name.split( '/' )[ 1 ];
	if ( ! blockName ) {
		throw new Error( `Invalid block name in ${ sourceManifest }` );
	}

	const relativeTarget =
		metadata.parent && ! genericBlocks.has( blockName )
			? path.join( 'inner-blocks', blockName, 'block.json' )
			: path.join( blockName, 'block.json' );
	const targetManifest = path.join( targetDirectory, relativeTarget );

	fs.mkdirSync( path.dirname( targetManifest ), { recursive: true } );
	fs.copyFileSync( sourceManifest, targetManifest );
}
NODE
