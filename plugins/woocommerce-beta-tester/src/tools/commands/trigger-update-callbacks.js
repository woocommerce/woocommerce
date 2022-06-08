/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const TRIGGER_UPDATE_CALLBACKS_ACTION_NAME =
	'runSelectedUpdateCallbacks';

export const TriggerUpdateCallbacks = () => {
	const { dbUpdateVersions } = useSelect( ( select ) => {
		const { getDBUpdateVersions } = select( STORE_KEY );
		return {
			dbUpdateVersions: getDBUpdateVersions(),
		};
	} );

	const { updateCommandParams } = useDispatch( STORE_KEY );

	function onCronChange( version ) {
		updateCommandParams( TRIGGER_UPDATE_CALLBACKS_ACTION_NAME, {
			version,
		} );
	}

	function getOptions() {
		return dbUpdateVersions.map( ( version ) => {
			return { label: version, value: version };
		} );
	}

	return (
		<div className="trigger-cron-job">
			{ ! dbUpdateVersions ? (
				<p>Loading ...</p>
			) : (
				<SelectControl
					label="Select a version to run"
					onChange={ onCronChange }
					labelPosition="side"
					options={ getOptions().reverse() }
				/>
			) }
		</div>
	);
};
