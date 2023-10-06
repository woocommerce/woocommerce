/**
 * External dependencies
 */
import { readdirSync, readFileSync } from 'fs';
import CLIError from '@wordpress/create-block/lib/cli-error';
import path from 'path';

/**
 * Internal dependencies
 */
import { PluginData } from './types';

/**
 * Get the plugin data.
 *
 * @return {Object} - Plugin data as key value pairs.
 */
export function getPluginData(): PluginData {
	const files = readdirSync( process.cwd() );
	for ( let i = 0; i < files.length; i++ ) {
		const file = path.join( process.cwd(), files[ i ] );
		if ( path.extname( file ) !== '.php' ) {
			continue;
		}
		const content = readFileSync( file, 'utf8' );
		const name = content.match( /^\s+\*\s*Plugin Name:\s*(.*)/m );
		if ( name && name.length > 1 ) {
			const description = content.match(
				/^\s+\*\s+Description:\s*(.*)/m
			);
			const textdomain = content.match( /^\s+\*\s*Text Domain:\s*(.*)/m );
			const version = content.match( /^\s+\*\s*Version:\s*(.*)/m );

			return {
				description: description ? description[ 1 ].trim() : undefined,
				name: name[ 1 ].trim(),
				textdomain: textdomain ? textdomain[ 1 ].trim() : undefined,
				version: version ? version[ 1 ].trim() : undefined,
				namespace: textdomain ? textdomain[ 1 ].trim() : undefined,
			};
		}
	}

	throw new CLIError( 'Plugin file not found.' );
}
