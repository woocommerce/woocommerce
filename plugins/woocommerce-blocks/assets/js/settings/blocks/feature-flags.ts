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
export const isExperimentalBlocksEnabled = (): boolean => {
	const { experimentalBlocksEnabled } = getSetting( 'wcBlocksConfig', {
		experimentalBlocksEnabled: false,
	} ) as WcBlocksConfig;

	return experimentalBlocksEnabled;
};

/**
 * Checks if experimental block styling is enabled.
 *
 * @return {boolean} True if experimental block styling is enabled.
 */
export const isExperimentalBlockStylingEnabled = (): boolean => {
	const { experimentalBlockStylingEnabled } = getSetting( 'wcBlocksConfig', {
		experimentalBlockStylingEnabled: false,
	} ) as WcBlocksConfig;

	return experimentalBlockStylingEnabled;
};
