const { exec } = require( 'node:child_process' );

export const DEFAULT_THEME = 'twentytwentythree';

export const activateTheme = ( themeName ) => {
	return new Promise( ( resolve, reject ) => {
		const command = `wp-env run tests-cli wp theme install ${ themeName } --activate`;

		exec( command, ( error, stdout ) => {
			if ( error ) {
				console.error( `Error executing command: ${ error }` );
				return reject( error );
			}

			resolve( stdout );
		} );
	} );
};
