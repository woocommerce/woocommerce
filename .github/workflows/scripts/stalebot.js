/**
 * Set the stalebot start date given a cron schedule.
 */

// You need to install this dependency as part of your workflow.
const core = require( '@actions/core' );

const ScheduleStartDate = () => {
	let scheduleStartDate;

	switch ( process.env.CRON_SCHEDULE ) {
		case '21 1 * * *':
			scheduleStartDate = '2022-01-01';
			break;
		case '31 2 * * *':
			scheduleStartDate = '2023-01-01';
			break;
		case '41 3 * * *':
			scheduleStartDate = '2023-08-01';
			break;
		default:
			scheduleStartDate = '2018-01-01';
			break;
	}

	core.setOutput( 'stale-start-date', scheduleStartDate );
};

ScheduleStartDate();
