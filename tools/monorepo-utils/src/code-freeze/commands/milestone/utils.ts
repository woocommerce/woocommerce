/**
 * External dependencies
 */
import { setOutput } from '@actions/core';
import { parse, inc } from 'semver';

const getMajorMinor = ( version: string ) => {
	const parsedVersion = parse( version );
	return `${ parsedVersion.major }.${ parsedVersion.minor }`;
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
	setOutput( 'nextReleaseVersion', getMajorMinor( nextReleaseVersion ) );
	setOutput( 'nextDevelopmentVersion', getMajorMinor( nextMilestone ) );
};

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
