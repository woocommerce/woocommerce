#!/bin/bash

# Copies every block.json under the Blocks source tree into the built assets directory so the PHP
# test suites can read block metadata without a full build. Mirrors the CopyWebpackPlugin rules in
# https://github.com/woocommerce/woocommerce/blob/84d1da7be3cbd3d8f40b17ad58729f668fd82b6a/plugins/woocommerce/client/blocks/bin/webpack-configs.js#L229-L256
# and must stay in sync with them. Unlike a plain copy, it first removes the previously generated
# manifests so an edited or deleted source manifest cannot leave a stale copy behind.

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
// Keep in sync with genericBlocks in webpack-entries.js.
const genericBlocks = new Set( [
	'accordion-group',
	'accordion-header',
	'accordion-item',
	'accordion-panel',
] );

const findManifests = ( directory ) =>
	fs
		.readdirSync( directory, { recursive: true } )
		.filter( ( entry ) => path.basename( entry ) === 'block.json' )
		.map( ( entry ) => path.join( directory, entry ) );

const sourceManifests = findManifests( sourceDirectory );
if ( sourceManifests.length === 0 ) {
	// Refuse to wipe the target when the source scan found nothing: that is a broken checkout, not an empty one.
	throw new Error( `No block metadata manifests found in ${ sourceDirectory }` );
}

fs.mkdirSync( targetDirectory, { recursive: true } );

for ( const targetManifest of findManifests( targetDirectory ) ) {
	fs.unlinkSync( targetManifest );
	// Prune the directories this copy created once they are empty, so a renamed or removed block leaves nothing behind.
	let directory = path.dirname( targetManifest );
	while ( directory !== targetDirectory && fs.readdirSync( directory ).length === 0 ) {
		fs.rmdirSync( directory );
		directory = path.dirname( directory );
	}
}

// The full build writes a metadata collection that overrides the individual block.json files at
// registration time; a stale one would mask the fresh copies until the next build, so drop it too.
fs.rmSync( path.join( targetDirectory, 'blocks-json.php' ), { force: true } );

for ( const sourceManifest of sourceManifests ) {
	const metadata = JSON.parse( fs.readFileSync( sourceManifest, 'utf8' ) );
	// Block names are `namespace/block-name`, the shape WordPress itself enforces. Reject anything else:
	// a name like `woocommerce/..` would otherwise resolve to a path outside the target directory.
	const blockName = /^[a-z][a-z0-9-]*\/([a-z][a-z0-9-]*)$/.exec( String( metadata.name ?? '' ) )?.[ 1 ];
	if ( ! blockName ) {
		throw new Error( `Invalid block name in ${ sourceManifest }` );
	}

	const targetManifest = path.join(
		targetDirectory,
		metadata.parent && ! genericBlocks.has( blockName ) ? path.join( 'inner-blocks', blockName ) : blockName,
		'block.json'
	);

	fs.mkdirSync( path.dirname( targetManifest ), { recursive: true } );
	fs.copyFileSync( sourceManifest, targetManifest );
}
NODE
