/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';
import { RequestOptions, request } from 'https';
import { IncomingMessage } from 'http';

export const execAsync = promisify( exec );

// Map just the raw value types of IncomingMessage to a new type for the response which includes a body string.
type HttpsResponse = {
	// I think it's fine to use this type this way just to exclude functions from the IncomingMessage type.
	// eslint-disable-next-line @typescript-eslint/ban-types
	[ K in keyof IncomingMessage as IncomingMessage[ K ] extends Function
		? never
		: K ]: IncomingMessage[ K ];
} & {
	body: string;
};

// A wrapper around https.request that returns a promise encapulating the response body and other response attributes.
export const requestAsync = (
	options: string | RequestOptions | URL,
	data?: string | Uint8Array | Buffer
) => {
	return new Promise< HttpsResponse >( ( resolve, reject ) => {
		const req = request( options, ( res ) => {
			let body = '';
			res.setEncoding( 'utf8' );
			res.on( 'data', ( chunk ) => {
				body += chunk;
			} );
			res.on( 'end', () => {
				const httpsResponse: HttpsResponse = {
					...res,
					body,
				};
				resolve( httpsResponse );
			} );
		} );
		req.on( 'error', ( err ) => {
			reject( err );
		} );
		if ( data ) {
			req.write( data );
		}
		req.end();
	} );
};
