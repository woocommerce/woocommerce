/**
 * External dependencies
 */
import { setOutput } from '@actions/core';

/**
 * Internal dependencies
 */
import { getMajorMinor } from '../../../core/version';

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
