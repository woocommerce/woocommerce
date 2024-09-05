/**
 * Internal dependencies
 */
import { octokitWithAuth, graphqlWithAuth } from '../../core/github/api';
import { Logger } from '../../core/logger';
import { requestPaginatedData, PaginatedDataTotals } from './github';
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
		count_items_processed: 0,
		count_items_available: 0,
		workflows: [],
	};
	const requestOptions = {
		owner,
		repo: name,
	};
	const endpoint = 'GET /repos/{owner}/{repo}/actions/workflows';

	const processPage = ( data, totals: PaginatedDataTotals ) => {
		const { total_count, workflows } = data;
		totals.count_items_available = total_count;
		totals.count_items_processed += workflows.length;
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

	if ( total_count === 0 ) {
		return totals;
	}

	totals.count_items_available = total_count;
	totals.count_items_processed += workflow_runs.length;

	Logger.notice(
		`Fetched workflows ${ totals.count_items_processed } / ${ totals.count_items_available }`
	);

	const { WORKFLOW_DURATION_CUTOFF_MINUTES } = config;

	workflow_runs.forEach( ( run ) => {
		totals[ run.conclusion ]++;
		if ( run.conclusion === 'success' ) {
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
	const initialTotals = {
		count_items_available: 0,
		nodeIds: [],
		times: [],
		success: 0,
		failure: 0,
		cancelled: 0,
		skipped: 0,
		count_items_processed: 0,
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

	return totals;
};

function splitArrayIntoChunks( array, n ) {
	const chunks = [];
	for ( let i = 0; i < array.length; i += n ) {
		const chunk = array.slice( i, i + n );
		chunks.push( chunk );
	}
	return chunks;
}

/**
 * Get compiled job data for a given workflow run.
 *
 * @param {Object} jobData Workflow run data
 * @return {Object} Compiled job data
 */
export const getCompiledJobData = ( jobData, result = {} ) => {
	const { nodes } = jobData;

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

/**
 * Get data on individual workflow runs.
 *
 * @param {Array} nodeIds Workflow node ids
 * @return {Object} Workflow run data
 */
export const getRunJobData = async ( nodeIds ) => {
	Logger.notice(
		`Processing individual data for the ${ nodeIds.length } successful workflow run(s)`
	);

	let compiledJobData = {};
	const perPage = 50;
	const gql = graphqlWithAuth();

	await Promise.all(
		splitArrayIntoChunks( nodeIds, perPage ).map(
			async ( pageOfNodeIds, index ) => {
				Logger.notice(
					`Fetched runs ${
						pageOfNodeIds.length === perPage
							? ( index + 1 ) * perPage
							: index * perPage + pageOfNodeIds.length
					} / ${ nodeIds.length }`
				);
				const data = await gql(
					`
				query($nodeIds: [ID!]!){ 
					nodes ( ids: $nodeIds ) {
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
			`,
					{
						nodeIds: pageOfNodeIds,
					}
				);

				compiledJobData = getCompiledJobData( data, compiledJobData );
			}
		)
	);

	return compiledJobData;
};
