/**
 * Internal dependencies
 */
import { octokitWithAuth } from '../../core/github/api';
import { Logger } from '../../core/logger';
import { requestPaginatedData, PaginatedDataTotals } from './github';

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
 * Calculate the mean value of an array of numbers.
 *
 * @param {Array} numbers Array of numbers
 * @return {number} Mean value
 */
export const calculateMean = ( numbers: Array< number > ) => {
	if ( numbers.length === 0 ) {
		return 0;
	}

	const sum = numbers.reduce( function ( a, b ) {
		return a + b;
	}, 0 );

	return sum / numbers.length;
};

/**
 * Calculate the median value of an array of numbers.
 *
 * @param {Array} numbers Array of numbers
 * @return {number} Median value
 */
export const calculateMedian = ( numbers: Array< number > ) => {
	if ( numbers.length === 0 ) {
		return 0;
	}

	// Sort the numbers in ascending order
	numbers.sort( function ( a, b ) {
		return a - b;
	} );

	const middleIndex = Math.floor( numbers.length / 2 );

	if ( numbers.length % 2 === 0 ) {
		// If the array length is even, return the average of the two middle values
		return ( numbers[ middleIndex - 1 ] + numbers[ middleIndex ] ) / 2;
	}
	// If the array length is odd, return the middle value
	return numbers[ middleIndex ];
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

	workflow_runs.forEach( ( run ) => {
		totals[ run.conclusion ]++;
		if ( run.conclusion === 'success' ) {
			const time =
				new Date( run.updated_at ).getTime() -
				new Date( run.run_started_at ).getTime();

			const thirtyMinutes = 1000 * 60 * 30;
			if ( time < thirtyMinutes ) {
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
export const getWorkflowData = async ( id: number ) => {
	const { data } = await octokitWithAuth().request(
		'GET /repos/{owner}/{repo}/actions/workflows/{workflow_id}',
		{
			owner: 'woocommerce',
			repo: 'woocommerce',
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
	id: number;
	owner: string;
	name: string;
	start: string;
	end: string;
} ) => {
	const { id, start, end, owner, name } = options;
	const workflowData = await getWorkflowData( id );

	const initialTotals = {
		total_count: 0,
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
	};
};

/**
 * Print workflow run results to the console.
 *
 * @param {Array} results Workflow run results
 */
export const logWorkflowRunResults = ( results ) => {
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
		],
		results.map( ( result ) => [
			result.name,
			result.total_count,
			result.success,
			result.failure,
			result.cancelled,
			result.average_time_in_minutes,
			result.median_time_in_minutes,
			result.longest_time_in_minutes,
			result.shortest_time_in_minutes,
		] )
	);
};
