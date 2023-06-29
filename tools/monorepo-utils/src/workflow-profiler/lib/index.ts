/**
 * Internal dependencies
 */
import { octokitWithAuth } from '../../core/github/api';
import { Logger } from '../../core/logger';

/**
 * Get all workflows from the WooCommerce repository.
 *
 * @param {string} owner - The owner of the repository.
 * @param {string} name  - The name of the repository.
 * @return Workflows and total count
 */
export const getAllWorkflows = async ( owner: string, name: string ) => {
	let totalWorkflows = [];
	const request = async ( page = 1, per_page = 100 ) => {
		try {
			const result = await octokitWithAuth().request(
				'GET /repos/{owner}/{repo}/actions/workflows',
				{
					owner,
					repo: name,
					page,
					per_page,
				}
			);

			const { data } = result;
			const { total_count, workflows } = data;
			totalWorkflows = totalWorkflows.concat( workflows );

			if ( total_count > totalWorkflows.length ) {
				await request( page + 1 );
			}
		} catch ( e ) {
			Logger.error( e );
		}
	};

	await request();
	return totalWorkflows;
};
