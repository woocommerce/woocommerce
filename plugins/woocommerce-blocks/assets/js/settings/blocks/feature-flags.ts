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
 * Check if experimental block styling features are enabled.
 *
 * @return {boolean} True if experimental block styling features are enabled.
 */
export const isExperimentalBlockStylingEnabled = (): boolean =>
	WC_BLOCKS_PHASE > 1;
