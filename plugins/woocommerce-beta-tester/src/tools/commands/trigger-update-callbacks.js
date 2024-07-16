/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const TRIGGER_UPDATE_CALLBACKS_ACTION_NAME =
	'runSelectedUpdateCallbacks';

export const TriggerUpdateCallbacks = () => {
	const dbUpdateVersions = useSelect(
		( select ) => select( STORE_KEY ).getDBUpdateVersions(),
		[]
	);
	const selectedVersion = useSelect(
		( select ) =>
			select( STORE_KEY ).getCommandParams(
				TRIGGER_UPDATE_CALLBACKS_ACTION_NAME
			).runSelectedUpdateCallbacks.version,
		[]
	);
	const { updateCommandParams } = useDispatch( STORE_KEY );

	function onChange( version ) {
		updateCommandParams( TRIGGER_UPDATE_CALLBACKS_ACTION_NAME, {
			version,
		} );
	}

	const options = useMemo(
		() =>
			dbUpdateVersions.map( ( version ) => ( {
				label: version,
				value: version,
			} ) ),
		[ dbUpdateVersions ]
	);

	return (
		<div className="select-description">
			{ ! dbUpdateVersions ? (
				<p>Loading ...</p>
			) : (
				<SelectControl
					label="Select a version to run"
					onChange={ onChange }
					labelPosition="side"
					options={ options }
					value={ selectedVersion }
				/>
			) }
		</div>
	);
};
