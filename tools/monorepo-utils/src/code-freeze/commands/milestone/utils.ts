/**
 * External dependencies
 */
import { parse, inc } from 'semver';
import { setOutput } from '@actions/core';

/**
 * Bumps the version according to WP rules.
 *
 * @param {string} version Version to increment
 * @return {string} Incremented version
 */
export const WPIncrement = ( version: string ): string => {
	const parsedVersion = parse( version );
	return inc( parsedVersion, parsedVersion.minor === 9 ? 'major' : 'minor' );
};

/**
 * Set Github outputs.
 *
 * @param {string} nextReleaseVersion Next release version
 * @param {string} nextMilestone      Next milestone
 */
export const setGithubMilestoneOutputs = (
	nextReleaseVersion: string,
	nextMilestone: string
): void => {
	setOutput( 'nextReleaseVersion', nextReleaseVersion );
	setOutput( 'nextDevelopmentVersion', nextMilestone );
};
