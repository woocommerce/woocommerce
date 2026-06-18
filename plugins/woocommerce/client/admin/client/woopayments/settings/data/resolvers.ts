/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NAMESPACE } from './constants';
import { updateSettings } from './actions';

export function* getSettings(): Generator<
	unknown,
	void,
	Record< string, unknown >
> {
	const path = `${ NAMESPACE }/settings`;

	try {
		const result = yield apiFetch( { path } );

		yield updateSettings( result );
	} catch ( e ) {
		yield dispatch( 'core/notices' ).createErrorNotice(
			__( 'Error retrieving settings.', 'woocommerce' )
		);
	}
}
