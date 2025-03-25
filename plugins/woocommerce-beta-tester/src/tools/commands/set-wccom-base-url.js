/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../data';

export const UPDATE_WCCOM_BASE_URL_ACTION_NAME = 'updateWccomBaseUrl';

export const SetWccomBaseUrl = () => {
	const url = useSelect(
		( select ) => select( store ).getWccomBaseUrl(),
		[]
	);
	const { updateCommandParams } = useDispatch( store );

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
