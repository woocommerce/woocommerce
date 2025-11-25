/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { addHistoryListener } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

export default async () => {
	const { onLoad, onHistoryChange } = dispatch( STORE_NAME );

	await onLoad();

	addHistoryListener( async () => {
		setTimeout( async () => {
			// Pass true to suppress deprecation warning for internal usage only.
			await onHistoryChange( true );
		}, 0 );
	} );
};
