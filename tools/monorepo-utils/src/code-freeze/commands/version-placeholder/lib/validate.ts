/**
 * External dependencies
 */
import { valid } from 'semver';

/**
 * Internal dependencies
 */
import { Logger } from '../../../../core/logger';

/**
 * Validate inputs.
 *
 * @param  plugin          plugin
 * @param  options         options
 * @param  options.version version
 */
export const validateArgs = async (
	version: string
): Promise< void > => {
	if ( ! valid( version ) ) {
		Logger.error(
			'Invalid version supplied, please pass in a semantically correct version.'
		);
	}

};
