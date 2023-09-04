/**
 * Internal dependencies
 */
import { Logger } from '../../core/logger';
import {
	calculateMean,
	calculateMedian,
	calculate90thPercentile,
} from './math';

/**
 * Print workflow run results to the console.
 *
 * @param {string} name Workflow name
 * @param {Object} data Workflow run results
 */
export const logWorkflowRunResults = ( name, data ) => {
	Logger.table(
		[
			'Workflow Name',
			'Total runs',
			'success',
			'failed',
			'cancelled',
			'average (min)',
			'median (min)',
			'longest (min)',
			'shortest (min)',
			'90th percentile (min)',
		],
		[
			[
				name,
				data.count_items_available.toString(),
				data.success.toString(),
				data.failure.toString(),
				data.cancelled.toString(),
				( calculateMean( data.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes,
				( calculateMedian( data.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes
				( Math.max( ...data.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes
				( Math.min( ...data.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes
				( calculate90thPercentile( data.times ) / 1000 / 60 ).toFixed(
					2
				), // in minutes
			],
		]
	);
};

/**
 * Log job data from a workflow run.
 *
 * @param {Object} data compiled job data
 */
export const logJobResults = ( data ) => {
	const rows = Object.keys( data ).map( ( jobName ) => {
		const job = data[ jobName ];

		return [
			jobName,
			( calculateMean( job.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes
			( calculateMedian( job.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes
			( Math.max( ...job.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes
			( Math.min( ...job.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes
			( calculate90thPercentile( job.times ) / 1000 / 60 ).toFixed( 2 ), // in minutes
		];
	} );
	Logger.table(
		[
			'Job Name',
			'average (min)',
			'median (min)',
			'longest (min)',
			'shortest (min)',
			'90th percentile (min)',
		],
		rows
	);
};

/**
 * Log job steps from a workflow run.
 *
 * @param {Object} data compiled job data
 */
export const logStepResults = ( data ) => {
	Object.keys( data ).forEach( ( jobName ) => {
		const job = data[ jobName ];

		const rows = Object.keys( job.steps ).map( ( stepName ) => {
			const step = job.steps[ stepName ];

			return [
				stepName,
				( calculateMean( step ) / 1000 / 60 ).toFixed( 2 ), // in minutes
				( calculateMedian( step ) / 1000 / 60 ).toFixed( 2 ), // in minutes
				( Math.max( ...step ) / 1000 / 60 ).toFixed( 2 ), // in minutes
				( Math.min( ...step ) / 1000 / 60 ).toFixed( 2 ), // in minutes
				( calculate90thPercentile( step ) / 1000 / 60 ).toFixed( 2 ), // in minutes
			];
		} );

		Logger.table(
			[
				`Steps for job: ${ jobName }`,
				'average (min)',
				'median (min)',
				'longest (min)',
				'shortest (min)',
				'90th percentile (min)',
			],
			rows
		);
	} );
};
