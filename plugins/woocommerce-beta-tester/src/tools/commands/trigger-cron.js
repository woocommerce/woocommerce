/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../data';

export const TRIGGER_CRON_ACTION_NAME = 'runSelectedCronJob';

export const TriggerCronJob = () => {
	const { cronList } = useSelect( ( select ) => {
		const { getCronJobs } = select( store );
		return {
			cronList: getCronJobs(),
		};
	} );
	const { updateCommandParams } = useDispatch( store );

	function onCronChange( selectedValue ) {
		const { hook, signature } = cronList[ selectedValue ];
		updateCommandParams( TRIGGER_CRON_ACTION_NAME, { hook, signature } );
	}

	function getOptions() {
		return Object.keys( cronList ).map( ( name ) => {
			return { label: name, value: name };
		} );
	}

	return (
		<div className="select-description">
			{ ! cronList ? (
				<p>Loading ...</p>
			) : (
				<SelectControl
					label="Select cron job to run"
					onChange={ onCronChange }
					labelPosition="side"
					options={ getOptions() }
				/>
			) }
		</div>
	);
};
