/**
 * External dependencies
 */
const { command } = require( 'execa' );
const { join } = require( 'path' );
const { info, error } = require( '../node_modules/@wordpress/create-block/lib/log' );
const path = require( 'path' );
const { readFileSync } = require( 'fs' );
const writePkg = require( 'write-pkg' );

// @todo share this between init and update
const packageScripts = {
	build: 'wp-scripts build',
	'check-engines': 'wp-scripts check-engines',
	'check-licenses': 'wp-scripts check-licenses',
	format: 'wp-scripts format',
	'lint:css': 'wp-scripts lint-style',
	'lint:js': 'wp-scripts lint-js',
	'lint:md:docs': 'wp-scripts lint-md-docs',
	'lint:pkg-json': 'wp-scripts lint-pkg-json',
	'packages-update': 'wp-scripts packages-update',
	'plugin-zip': 'wp-scripts plugin-zip',
	start: 'wp-scripts start',
	'test:e2e': 'wp-scripts test-e2e',
	'test:unit': 'wp-scripts test-unit-js',
};

module.exports = async () => {
	const cwd = join( process.cwd() );

	info( '' );
	info(
		'Installing `@wordpress/scripts` package. It might take a couple of minutes...'
	);
	await command( 'npm install @wordpress/scripts --save-dev', {
		cwd,
	} );

    const package = JSON.parse(
        readFileSync( path.join( cwd, 'package.json' ), 'utf8' )
    );

	info( '' );
	info(
		'Adding `@wordpress/scripts` scripts and helper utils to package.'
	);
    const updatedPackage = { ...package };
    Object.keys( packageScripts ).forEach( ( script ) => {
        const command = packageScripts[ script ];
        if ( package.scripts[ script ] && package.scripts[ script ].indexOf( command ) < 0 ) {
            error(
                `The script "${ script }" already exists in the package.  Please add "${ command }" to this script if you'd like to use this script.`
            );
        } else if ( ! package.scripts[ script ] ) {
            info(
                `Adding the script "${ script }" to the package.`
            );
            updatedPackage.scripts[ script ] = command;
        }
    } );

    await writePkg( updatedPackage );

	info( '' );
	info( 'Formatting JavaScript files.' );
	await command( 'npm run format', {
		cwd,
	} );

	info( '' );
	info( 'Building plugin assets.' );
	await command( 'npm run build', {
		cwd,
	} );
};
