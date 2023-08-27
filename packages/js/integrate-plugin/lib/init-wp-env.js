/**
 * External dependencies
 */
const { command } = require( 'execa' );
const { join } = require( 'path' );
const { writeFile } = require( 'fs' ).promises;
const { info } = require( '../node_modules/@wordpress/create-block/lib/log' );

module.exports = async () => {
	const cwd = join( process.cwd() );

	info( '' );
	info(
		'Installing `@wordpress/env` package. It might take a couple of minutes...'
	);
	await command( 'npm install @wordpress/env --save-dev', {
		cwd,
	} );

	info( '' );
	info( 'Configuring `@wordpress/env`...' );
	await writeFile(
		join( cwd, '.wp-env.json' ),
		JSON.stringify(
			{
				core: 'WordPress/WordPress',
				plugins: [ '.' ],
			},
			null,
			'\t'
		)
	);
};
