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
