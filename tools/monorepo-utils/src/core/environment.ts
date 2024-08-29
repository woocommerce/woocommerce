/**
 * Internal dependencies
 */
import { Logger } from './logger';

export const getEnvVar = ( varName: string, isRequired = false ) => {
	const value = process.env[ varName ];

	if ( value === undefined && isRequired ) {
		Logger.error(
			`You need to provide a value for ${ varName } in your environment either via an environment variable or the .env file.`
		);
	}

	return value || '';
};

export const isGithubCI = () => {
	return !! ( process.env.CI || process.env.GITHUB_ACTIONS );
};
