/**
 * External dependencies.
 */
import { SelectControl } from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

export const TRIGGER_CRON_ACTION_NAME = 'runSelectedCronJob';

const TriggerCron = ({ cronList, updateCommandParams }) => {
	function onCronChange(selectedValue) {
		const { hook, signature } = cronList[selectedValue];
		updateCommandParams(TRIGGER_CRON_ACTION_NAME, { hook, signature });
	}

	function getOptions(cronList) {
		return Object.keys(cronList).map((name) => {
			return { label: name, value: name };
		});
	}

	return (
		<div className="trigger-cron-job">
			{!cronList ? (
				<p>Loading ...</p>
			) : (
				<SelectControl
					label="Select cron job to run"
					onChange={onCronChange}
					labelPosition="side"
					options={getOptions(cronList)}
				/>
			)}
		</div>
	);
};

export const TriggerCronJob = compose(
	withSelect((select) => {
		const { getCronJobs } = select(STORE_KEY);
		return {
			cronList: getCronJobs(),
		};
	}),
	withDispatch((dispatch) => {
		const { updateCommandParams } = dispatch(STORE_KEY);
		return { updateCommandParams };
	})
)(TriggerCron);
