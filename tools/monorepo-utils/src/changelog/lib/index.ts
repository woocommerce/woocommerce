/**
 * Internal dependencies
 */
import { octokitWithAuth } from '../../core/github/api';
export const getPRDescription = async ( options, prNumber ) => {
	const { owner, name } = options;
	const pr = await octokitWithAuth.request(
		'GET /repos/{owner}/{repo}/pulls/{pull_number}',
		{
			owner,
			repo: name,
			pull_number: prNumber,
		}
	);
	return pr.data;
};
