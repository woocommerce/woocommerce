/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { WcBlocksConfig } from './constants';

/**
 * Checks if experimental blocks are enabled.
 *
 * @return {boolean} True if this experimental blocks are enabled.
 */
export const isExperimentalBuild = (): boolean => {
	const { experimentalBlocksEnabled } = getSetting( 'wcBlocksConfig', {
		experimentalBlocksEnabled: false,
	} ) as WcBlocksConfig;

	return experimentalBlocksEnabled;
};

/**
 * Checks if we're executing the code in an feature plugin or experimental build mode.
 *
 * @return {boolean} True if this is an experimental or feature plugin build, false otherwise.
 */
export const isExperimentalBlockStylingEnabled = (): boolean => {
	const { experimentalBlockStylingEnabled } = getSetting( 'wcBlocksConfig', {
		experimentalBlockStylingEnabled: false,
	} ) as WcBlocksConfig;

	return experimentalBlockStylingEnabled;
};
