/**
 * External dependencies
 */
const { readdirSync, readFileSync } = require( 'fs' );
const CLIError = require( '../node_modules/@wordpress/create-block/lib/cli-error' );
const path = require('path');

/**
 * Get the plugin data.
 *
 * @return {Object} - Plugin data as key value pairs.
 */
module.exports = () => {
    var files = readdirSync( process.cwd() );
    for (var i = 0; i < files.length; i++) {
        const file = path.join( process.cwd(), files[i] );
        if ( path.extname( file ) !== '.php' ) {
            continue;
        }
        const content = readFileSync( file, 'utf8' );
        const name = content.match( /^\s+\*\s+Plugin Name:\s+(.*)/m );
        if ( name && name.length > 1 ) {
            const description = content.match( /^\s+\*\s+Description:\s+(.*)/m );
            const textdomain = content.match( /^\s+\*\s+Text Domain:\s+(.*)/m );
            const version = content.match( /^\s+\*\s+Version:\s+(.*)/m );

            return {
                description: description && description[1],
                name: name[1],
                textdomain: textdomain && textdomain[1],
                version: version && version[1],
            }
        }
    };

    throw new CLIError( 'Plugin file not found.' );
};