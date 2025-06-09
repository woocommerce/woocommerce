/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';

// Resolves channel IDs from the SLACK_CHANNELS env variable (comma-separated).
// Throws if not set or empty.
export function resolveChannels(): string[] {
	const value = process.env.SLACK_CHANNELS;
	if ( ! value ) {
		Logger.error(
			'SLACK_CHANNELS environment variable must be set with comma-separated channel IDs.'
		);
	}
	return value
		.split( ',' )
		.map( ( v ) => v.trim() )
		.filter( Boolean );
}
