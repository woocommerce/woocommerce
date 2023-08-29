/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export async function handlePrompt(
	message: string = __( 'Enter a value', 'woocommerce' ),
	defaultValue?: string
) {
	return new Promise< string >( ( resolve, reject ) => {
		// eslint-disable-next-line no-alert
		const value = window.prompt( message, defaultValue );

		if ( value === null ) {
			reject();
		} else {
			resolve( value );
		}
	} );
}
