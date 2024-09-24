/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const UPDATE_WCCOM_BASE_URL_ACTION_NAME = 'updateWccomBaseUrl';

export const SetWccomBaseUrl = () => {
	const url = useSelect(
		( select ) => select( STORE_KEY ).getWccomBaseUrl(),
		[]
	);
	const { updateCommandParams } = useDispatch( STORE_KEY );

	function onUpdate( newUrl ) {
		updateCommandParams( UPDATE_WCCOM_BASE_URL_ACTION_NAME, {
			url: newUrl,
		} );
	}

	return (
		<div className="wccom-base-url-control">
			{ url === undefined ? (
				<p>Loading...</p>
			) : (
				<TextControl value={ url } onChange={ onUpdate } />
			) }
		</div>
	);
};
