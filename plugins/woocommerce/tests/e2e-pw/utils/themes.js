const { exec } = require( 'node:child_process' );

export const activateTheme = ( themeName ) => {
	return new Promise( ( resolve, reject ) => {
		const command = `wp-env run tests-cli wp theme activate ${ themeName }`;

		exec( command, ( error, stdout, stderr ) => {
			if ( error ) {
				console.error( `Error executing command: ${ error }` );
				return reject( error );
			}

			resolve( stdout );
		} );
	} );
};
