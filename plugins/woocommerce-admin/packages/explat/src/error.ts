/**
 * Internal dependencies
 */
import { isDevelopmentMode } from './utils';

export const logError = (
	error: Record< string, string > & { message: string }
): void => {
	const onLoggingError = ( e: unknown ) => {
		if ( isDevelopmentMode ) {
			console.error( '[ExPlat] Unable to send error to server:', e ); // eslint-disable-line no-console
		}
	};

	try {
		const { message, ...properties } = error;
		const logStashError = {
			message,
			properties: {
				...properties,
				context: 'explat',
				explat_client: 'woocommerce',
			},
		};

		if ( isDevelopmentMode ) {
			console.error( '[ExPlat] ', error.message, error ); // eslint-disable-line no-console
		} else {
			if ( ! window.wcTracks?.isEnabled ) {
				throw new Error(
					`Tracking is disabled, can't send error to the server`
				);
			}

			const body = new window.FormData();
			body.append( 'error', JSON.stringify( logStashError ) );
			window
				.fetch( 'https://public-api.wordpress.com/rest/v1.1/js-error', {
					method: 'POST',
					body,
				} )
				.catch( onLoggingError );
		}
	} catch ( e ) {
		onLoggingError( e );
	}
};
