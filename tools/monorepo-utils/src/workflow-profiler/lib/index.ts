/**
 * Internal dependencies
 */
import { octokitWithAuth, graphqlWithAuth } from '../../core/github/api';
import { Logger } from '../../core/logger';
import { requestPaginatedData, PaginatedDataTotals } from './github';
import {
	calculateMean,
	calculateMedian,
	calculate90thPercentile,
} from './math';
import config from '../config';

/**
 * Get all workflows from the WooCommerce repository.
 *
 * @param {string} owner - The owner of the repository.
 * @param {string} name  - The name of the repository.
 * @return Workflows and total count
 */
export const getAllWorkflows = async ( owner: string, name: string ) => {
	const initialTotals = {
		count: 0,
		total_count: 0,
		workflows: [],
	};
	const requestOptions = {
		owner,
		repo: name,
	};
	const endpoint = 'GET /repos/{owner}/{repo}/actions/workflows';

	const processPage = ( data, totals: PaginatedDataTotals ) => {
		const { total_count, workflows } = data;
		totals.total_count = total_count;
		totals.count += workflows.length;
		totals.workflows = totals.workflows.concat( workflows );
		return totals;
	};

	const totals = await requestPaginatedData(
		initialTotals,
		endpoint,
		requestOptions,
		processPage
	);

	return totals.workflows;
};

/**
 * Handle on page of workflow runs.
 *
 * @param {Object} data   Github workflow run data
 * @param {Object} totals totals
 * @return {Object} totals
 */
const processWorkflowRunPage = ( data, totals: PaginatedDataTotals ) => {
	const { workflow_runs, total_count } = data;
	totals.total_count = total_count;
	totals.count += workflow_runs.length;
	const { WORKFLOW_DURATION_CUTOFF_MINUTES } = config;

	workflow_runs.forEach( ( run ) => {
		totals[ run.conclusion ]++;
		if ( run.conclusion === 'success' ) {
			totals.runIds.push( run.id );
			totals.nodeIds.push( run.node_id );
			const time =
				new Date( run.updated_at ).getTime() -
				new Date( run.run_started_at ).getTime();

			const maxDuration = 1000 * 60 * WORKFLOW_DURATION_CUTOFF_MINUTES;
			if ( time < maxDuration ) {
				totals.times.push( time );
			}
		}
	} );

	return totals;
};

/**
 * Get workflow run data for a given workflow.
 *
 * @param {number} id Workflow id
 * @return {Object} Workflow data
 */
export const getWorkflowData = async (
	id: number | string,
	owner: string,
	name: string
) => {
	const { data } = await octokitWithAuth().request(
		'GET /repos/{owner}/{repo}/actions/workflows/{workflow_id}',
		{
			owner,
			repo: name,
			workflow_id: id,
		}
	);

	return data;
};

/**
 * Get workflow run data for a given workflow.
 *
 * @param {Object} options       request options
 * @param {Object} options.id    workflow id
 * @param {Object} options.owner repo owner
 * @param {Object} options.name  repo name
 * @param {Object} options.start start date
 * @param {Object} options.end   end date
 * @return {Object} totals
 */
export const getWorkflowRunData = async ( options: {
	id: number | string;
	owner: string;
	name: string;
	start: string;
	end: string;
} ) => {
	const { id, start, end, owner, name } = options;
	const workflowData = await getWorkflowData( id, owner, name );

	const initialTotals = {
		total_count: 0,
		runIds: [],
		nodeIds: [],
		times: [],
		success: 0,
		failure: 0,
		cancelled: 0,
		count: 0,
	};
	const requestOptions = {
		owner,
		repo: name,
		workflow_id: id,
		created: `${ start }..${ end }`,
	};
	const workflowRunEndpoint =
		'GET /repos/{owner}/{repo}/actions/workflows/{workflow_id}/runs';

	const totals = await requestPaginatedData(
		initialTotals,
		workflowRunEndpoint,
		requestOptions,
		processWorkflowRunPage
	);

	return {
		name: workflowData.name,
		id,
		total_count: totals.total_count.toString(),
		success: totals.success.toString(),
		failure: totals.failure.toString(),
		cancelled: totals.cancelled.toString(),
		average_time_in_minutes: (
			calculateMean( totals.times ) /
			1000 /
			60
		).toFixed( 2 ), // in minutes
		median_time_in_minutes: (
			calculateMedian( totals.times ) /
			1000 /
			60
		).toFixed( 2 ), // in minutes
		longest_time_in_minutes: (
			Math.max( ...totals.times ) /
			1000 /
			60
		).toFixed( 2 ), // in minutes
		shortest_time_in_minutes: (
			Math.min( ...totals.times ) /
			1000 /
			60
		).toFixed( 2 ), // in minutes
		'90th_percentile_in_minutes': (
			calculate90thPercentile( totals.times ) /
			1000 /
			60
		).toFixed( 2 ),
		runIds: totals.runIds,
		nodeIds: totals.nodeIds,
	};
};

/**
 * Print workflow run results to the console.
 *
 * @param {Object} data Workflow run results
 */
export const logWorkflowRunResults = ( data ) => {
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
				data.name,
				data.total_count,
				data.success,
				data.failure,
				data.cancelled,
				data.average_time_in_minutes,
				data.median_time_in_minutes,
				data.longest_time_in_minutes,
				data.shortest_time_in_minutes,
				data[ '90th_percentile_in_minutes' ],
			],
		]
	);
};

export const getRunJobData = async ( nodeIds ) => {
	const gql = graphqlWithAuth();
	const str = nodeIds.map( ( id ) => `"${ id }"` ).join( ', ' );
	return await gql( `
			{ 
				nodes ( ids: [ ${ str } ] ) {
				... on WorkflowRun {
					id
					workflow {
						id
						name
					}
					checkSuite {
						checkRuns ( first: 20, filterBy: { status: COMPLETED } ) {
								nodes {
									name
									id
									startedAt
									completedAt
									steps ( first: 50 ) {
										nodes {
											name
											startedAt
											completedAt
										}
									}
								}
							}
						}
					}
				}
			}
		` );
};

export const getCompiledJobData = ( jobData ) => {
	const { nodes } = jobData;
	const result = {};

	nodes.forEach( ( node ) => {
		const jobs = node.checkSuite.checkRuns.nodes;
		jobs.forEach( ( job ) => {
			const { name, startedAt, completedAt } = job;

			const time =
				new Date( completedAt ).getTime() -
				new Date( startedAt ).getTime();

			if ( ! result[ name ] ) {
				result[ name ] = {
					times: [],
					steps: {},
				};
			}

			result[ name ].times.push( time );

			const steps = job.steps.nodes;
			steps.forEach( ( step ) => {
				const {
					name: stepName,
					startedAt: stepStart,
					completedAt: stepCompleted,
				} = step;

				if (
					stepName === 'Set up job' ||
					stepName === 'Complete job' ||
					stepName.startsWith( 'Post ' )
				) {
					return;
				}
				const stepTime =
					new Date( stepCompleted ).getTime() -
					new Date( stepStart ).getTime();

				if ( ! result[ name ].steps[ stepName ] ) {
					result[ name ].steps[ stepName ] = [];
				}

				result[ name ].steps[ stepName ].push( stepTime );
			} );
		} );
	} );

	return result;
};

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
