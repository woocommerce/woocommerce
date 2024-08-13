/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const FORCE_WCCOM_ENDPOINT_ERRORS = 'forceWccomEndpointErrors';

const OPTIONS = [
	{ label: 'Timeout requests', value: 'timeout' },
	{ label: '500 error', value: 'error' },
	{ label: 'Disabled', value: 'disabled' },
];

export const SetWccomErrros = () => {
	const comingSoonMode = useSelect(
		( select ) => select( STORE_KEY ).getComingSoonMode(),
		[]
	);
	const { updateCommandParams } = useDispatch( STORE_KEY );

	function onChange( mode ) {
		updateCommandParams( FORCE_WCCOM_ENDPOINT_ERRORS, {
			mode,
		} );
	}

	return (
		<div className="select-description">
			{ ! comingSoonMode ? (
				<p>Loading ...</p>
			) : (
				<SelectControl
					label="Mode"
					labelPosition="side"
					value={ comingSoonMode }
					onChange={ onChange }
					options={ OPTIONS }
				/>
			) }
		</div>
	);
};
