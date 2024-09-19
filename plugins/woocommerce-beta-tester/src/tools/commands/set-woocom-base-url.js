/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const UPDATE_WOOCOM_BASE_URL_ACTION_NAME = 'updateWoocomBaseUrl';

export const SetWoocomBaseUrl = () => {
	const url = useSelect(
		( select ) => select( STORE_KEY ).getWoocomBaseUrl(),
		[]
	);
	const { updateCommandParams } = useDispatch( STORE_KEY );

	function onUpdate( newUrl ) {
		updateCommandParams( UPDATE_WOOCOM_BASE_URL_ACTION_NAME, {
			url: newUrl,
		} );
	}

	return (
		<div className="woocom-base-url-control">
			{ url === undefined ? (
				<p>Loading...</p>
			) : (
				<TextControl value={ url } onChange={ onUpdate } />
			) }
		</div>
	);
};
