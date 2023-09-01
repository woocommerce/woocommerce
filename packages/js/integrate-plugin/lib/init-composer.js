/**
 * External dependencies
 */
const { command } = require( 'execa' );
const { existsSync } = require( 'fs' );
const { join } = require( 'path' );
const { info } = require( '../node_modules/@wordpress/create-block/lib/log' );
const { writeFile } = require( 'fs' ).promises;

module.exports = async ( {
	textdomain,
	composerDependencies,
	composerDevDependencies,
} ) => {
	const cwd = join( process.cwd() );

	if ( ! existsSync( join( process.cwd(), 'composer.json' ) ) ) {
		info( '' );
		info( 'Creating a "composer.json" file.' );
	
		const config = {
			name: `${textdomain}/${textdomain}`,
		};
	
		await writeFile(
			join( cwd, 'composer.json' ),
			JSON.stringify( config, null, 4 ),
		);
	}

	// @todo This should probably be offloaded into templates to allow more configuration.
	await command( `composer config --no-plugins allow-plugins.automattic/jetpack-autoloader true`, {
		cwd,
	} );

	if ( composerDependencies && composerDependencies.length ) {
		info( '' );
		info(
			'Installing composer dependencies...'
		);
		for ( const packageArg of composerDependencies ) {
			info( '' );
			info( `Installing "${ packageArg }".` );
			await command( `composer require ${ packageArg }`, {
				cwd,
			} );
		}
	}

	if ( composerDevDependencies && composerDevDependencies.length ) {
		info( '' );
		info(
			'Installing composer dev dependencies...'
		);
		for ( const packageArg of composerDevDependencies ) {
			info( '' );
			info( `Installing "${ packageArg }".` );
			await command( `composer require --dev ${ packageArg }`, {
				cwd,
			} );
		}
	}
};
