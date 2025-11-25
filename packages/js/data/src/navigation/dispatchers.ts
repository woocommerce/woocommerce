/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { addHistoryListener } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { INTERNAL_CALL } from '.';

export default async () => {
	const { onLoad, onHistoryChange } = dispatch( STORE_NAME );

	await onLoad();

	addHistoryListener( async () => {
		setTimeout( async () => {
			// @ts-expect-error onHistoryChange accepts no parameters, but we pass INTERNAL_CALL
			// to suppress deprecation warnings for internal usage. The wrapper in index.ts
			// handles this symbol to skip the deprecation message.
			await onHistoryChange( INTERNAL_CALL );
		}, 0 );
	} );
};
