/**
 * External dependencies
 */
import { getFilename, getPatches } from 'cli-core/src/git';

export const scanForDBChanges = ( content: string ) => {
	const matchPatches = /^a\/(.+).php/g;
	const patches = getPatches( content, matchPatches );
	const databaseUpdatePatch = patches.find( ( patch ) => {
		const lines = patch.split( '\n' );
		const filepath = getFilename( lines[ 0 ] );
		return filepath.includes( 'class-wc-install.php' );
	} );

	if ( ! databaseUpdatePatch ) {
		return null;
	}

	const updateFunctionRegex =
		/\+{1,2}\s*'(\d.\d.\d)' => array\(\n\+{1,2}\s*'(.*)',\n\+{1,2}\s*\),/m;
	const match = databaseUpdatePatch.match( updateFunctionRegex );

	if ( ! match ) {
		return null;
	}
	const updateFunctionVersion = match[ 1 ];
	const updateFunctionName = match[ 2 ];

	return { updateFunctionName, updateFunctionVersion };
};
