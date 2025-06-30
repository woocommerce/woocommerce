/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../data';

export const UPDATE_COMING_SOON_MODE_ACTION_NAME = 'updateComingSoonMode';

const OPTIONS = [
	{ label: 'Whole Site', value: 'site' },
	{ label: 'Store Only', value: 'store' },
	{ label: 'Disabled', value: 'disabled' },
];

export const SetComingSoonMode = () => {
	const comingSoonMode = useSelect(
		( select ) => select( store ).getComingSoonMode(),
		[]
	);
	const { updateCommandParams } = useDispatch( store );

	function onChange( mode ) {
		updateCommandParams( UPDATE_COMING_SOON_MODE_ACTION_NAME, {
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
