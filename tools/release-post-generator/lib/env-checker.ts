/**
 * Internal dependencies
 */
import { Logger } from './logger';

export const checkEnv = () => {
	const { GITHUB_ACCESS_TOKEN } = process.env;

	if ( ! GITHUB_ACCESS_TOKEN ) {
		Logger.error(
			'You need a Github access token to run this script, add GITHUB_ACCESS_TOKEN to your environment variables or .env file'
		);
	}
};
