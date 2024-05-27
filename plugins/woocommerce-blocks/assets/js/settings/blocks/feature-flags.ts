/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { WC_BLOCKS_PHASE, WcBlocksConfig } from './constants';

/**
 * Checks if experimental blocks are enabled.
 *
 * @return {boolean} True if this experimental blocks are enabled.
 */
export const isExperimentalBlocksEnabled = (): boolean => {
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
export const isFeaturePluginBuild = (): boolean => WC_BLOCKS_PHASE > 1;
