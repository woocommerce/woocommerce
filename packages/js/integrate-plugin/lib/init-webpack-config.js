/**
 * External dependencies
 */
const { existsSync } = require( 'fs' );
const { join } = require( 'path' );
const { info, error } = require( '../node_modules/@wordpress/create-block/lib/log' );

module.exports = async ( {} ) => {
    const cwd = join( process.cwd() );

    if ( existsSync( `${ cwd }/webpack.config.js` ) ) {
		initWebpackConfig( view );
		const webpackConfig = readFileSync(
			`${ cwd }/webpack.config.js`,
			'utf8'
		);
		if ( webpackConfig.indexOf( '@wordpress/scripts' ) < 0 ) {
            info( '' );
			error(
				`The @wordpress/scripts config was not found in your webpack config.  Consider adding it to your config to allow blocks to work.`
			);
		}
	} else {
        info( '' );
        info( 'No webpack config file found.  Using the default configuration.' );
    }
};
