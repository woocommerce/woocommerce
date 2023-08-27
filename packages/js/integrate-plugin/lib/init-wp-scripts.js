/**
 * External dependencies
 */
const { command } = require( 'execa' );
const { join } = require( 'path' );
const { info } = require( '../node_modules/@wordpress/create-block/lib/log' );

module.exports = async () => {
	const cwd = join( process.cwd() );

	info( '' );
	info(
		'Installing `@wordpress/scripts` package. It might take a couple of minutes...'
	);
	await command( 'npm install @wordpress/scripts --save-dev', {
		cwd,
	} );

    // @todo add script commands to package.json

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
