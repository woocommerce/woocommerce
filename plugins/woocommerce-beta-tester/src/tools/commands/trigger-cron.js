/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const TRIGGER_CRON_ACTION_NAME = 'runSelectedCronJob';

export const TriggerCronJob = () => {
	const { cronList } = useSelect( ( select ) => {
		const { getCronJobs } = select( STORE_KEY );
		return {
			cronList: getCronJobs(),
		};
	} );
	const { updateCommandParams } = useDispatch( STORE_KEY );

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
		<div className="trigger-cron-job">
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
