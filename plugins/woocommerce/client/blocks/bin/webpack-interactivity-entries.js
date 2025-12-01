const path = require( 'path' );
const fs = require( 'fs' );
const glob = require( 'glob' );

function blockSupportsInteractivity( blockJson ) {
	if ( typeof blockJson?.supports?.interactivity === 'object' ) {
		return blockJson.supports.interactivity?.interactive === true;
	}

	return blockJson?.supports?.interactivity === true;
}

function findInteractivityBlockAssets( dir = [] ) {
	const additionalPatterns = [ 'frontend.*s', 'style.scss', 'editor.scss' ];
	let results = [];
	const ents = fs.readdirSync( dir, { withFileTypes: true } );

	for ( const entry of ents ) {
		const fullPath = path.join( dir, entry.name );
		if ( entry.isDirectory() ) {
			results = results.concat(
				findInteractivityBlockAssets( fullPath, additionalPatterns )
			);
		} else if ( entry.isFile() && entry.name === 'block.json' ) {
			// parse the json file and determine if its a block that supports interactivity.
			const blockJson = JSON.parse( fs.readFileSync( fullPath, 'utf8' ) );

			if ( blockSupportsInteractivity( blockJson ) ) {
				const blockDir = path.dirname( fullPath );
				const assets = additionalPatterns.flatMap( ( pattern ) =>
					glob.sync( pattern, { cwd: blockDir, absolute: true } )
				);

				// For block.json's viewScriptModule, style, editorStyle, check if the file exists and warn
				// if it doesn't, so we don't try enqueue non-existent assets.
				if ( blockJson.viewScriptModule ) {
					if (
						! fs.existsSync( path.join( blockDir, 'frontend.ts' ) )
					) {
						// eslint-disable-next-line no-console
						console.warn(
							`viewScriptModule was declared in ${ blockJson.name } block.json but no frontend.ts file exists.`
						);
					}
				}

				if ( blockJson.style ) {
					if (
						! fs.existsSync( path.join( blockDir, 'style.scss' ) )
					) {
						// eslint-disable-next-line no-console
						console.warn(
							`style was declared in ${ blockJson.name } block.json but no style.scss file exists.`
						);
					}
				}

				if ( blockJson.editorStyle ) {
					if (
						! fs.existsSync( path.join( blockDir, 'editor.scss' ) )
					) {
						// eslint-disable-next-line no-console
						console.warn(
							`editorStyle was declared in ${ blockJson.name } block.json but no editor.scss file exists.`
						);
					}
				}

				results.push( {
					blockName: blockJson.name,
					blockJson: fullPath,
					assets,
				} );
			}
		}
	}

	return results;
}

const interactivityBlocks = findInteractivityBlockAssets(
	path.resolve( __dirname, '../assets/js' )
);

const scriptModuleEntries = interactivityBlocks.reduce( ( acc, block ) => {
	const frontendFile = block.assets.find( ( f ) => f.includes( 'frontend' ) );
	if ( frontendFile ) {
		acc[ block.blockName ] = frontendFile;
	}
	return acc;
}, {} );

const styleEntries = interactivityBlocks.reduce( ( acc, block ) => {
	const styleFile = block.assets.find( ( f ) => f.includes( 'style' ) );
	if ( styleFile ) {
		acc[ `${ block.blockName }-style` ] = styleFile;
	}
	return acc;
}, {} );

const editorStyleEntries = interactivityBlocks.reduce( ( acc, block ) => {
	const editorFile = block.assets.find( ( f ) => f.includes( 'editor' ) );
	if ( editorFile ) {
		acc[ `${ block.blockName }-editor` ] = editorFile;
	}
	return acc;
}, {} );

module.exports = {
	scriptModuleEntries,
	styleEntries,
	editorStyleEntries,
};
