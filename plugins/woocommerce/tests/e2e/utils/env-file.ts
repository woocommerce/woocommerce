/**
 * External dependencies
 */
import { readFileSync } from 'fs';

/**
 * Loads a .env file into process.env without overriding variables that are already set.
 *
 * Parses like dotenv 10 rather than process.loadEnvFile(): an unquoted value is kept verbatim,
 * so a `#` in it (e.g. in a password in the encrypted CI envs) is not treated as a comment.
 *
 * @param file Path to the .env file.
 */
export function loadEnvFile( file: string ) {
	const parsed: Record< string, string > = {};

	for ( const line of readFileSync( file, 'utf8' ).split( /\r\n|\n|\r/ ) ) {
		const match = line.match( /^\s*([\w.-]+)\s*=\s*(.*)?\s*$/ );
		if ( ! match ) {
			continue;
		}

		let value = match[ 2 ] ?? '';
		const quote = value[ 0 ];
		if (
			( quote === '"' || quote === "'" ) &&
			value.length > 1 &&
			value.endsWith( quote )
		) {
			value = value.slice( 1, -1 );
			if ( quote === '"' ) {
				value = value.replace( /\\n/g, '\n' );
			}
		} else {
			value = value.trim();
		}

		parsed[ match[ 1 ] ] = value;
	}

	for ( const [ key, value ] of Object.entries( parsed ) ) {
		if ( ! Object.prototype.hasOwnProperty.call( process.env, key ) ) {
			process.env[ key ] = value;
		}
	}
}
