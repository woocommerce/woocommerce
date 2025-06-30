/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { store } from '../data';

export const TRIGGER_UPDATE_CALLBACKS_ACTION_NAME =
	'runSelectedUpdateCallbacks';

export const TriggerUpdateCallbacks = () => {
	const dbUpdateVersions = useSelect(
		( select ) => select( store ).getDBUpdateVersions(),
		[]
	);
	const selectedVersion = useSelect(
		( select ) =>
			select( store ).getCommandParams(
				TRIGGER_UPDATE_CALLBACKS_ACTION_NAME
			).runSelectedUpdateCallbacks.version,
		[]
	);
	const { updateCommandParams } = useDispatch( store );

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
