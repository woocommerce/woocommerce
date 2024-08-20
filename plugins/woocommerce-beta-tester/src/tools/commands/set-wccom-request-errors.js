/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const UPDATE_WCCOM_REQUEST_ERRORS_MODE = 'updateWccomRequestErrorsMode';

const OPTIONS = [
	{ label: 'Timeout after 6 seconds', value: 'timeout' },
	{ label: '500', value: 'error' },
	{ label: 'Disabled', value: 'disabled' },
];

export const SetWccomRequestErrros = () => {
	const errorsMode = useSelect(
		( select ) => select( STORE_KEY ).getWccomRequestErrorsMode(),
		[]
	);
	const { updateCommandParams } = useDispatch( STORE_KEY );

	function onChange( mode ) {
		updateCommandParams( UPDATE_WCCOM_REQUEST_ERRORS_MODE, {
			mode,
		} );
	}

	return (
		<div className="select-description">
			{ ! errorsMode ? (
				<p>Loading ...</p>
			) : (
				<SelectControl
					label="Error Mode"
					labelPosition="side"
					value={ errorsMode }
					onChange={ onChange }
					options={ OPTIONS }
				/>
			) }
		</div>
	);
};
