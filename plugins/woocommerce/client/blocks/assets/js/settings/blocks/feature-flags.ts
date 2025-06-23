/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { getConfig } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { WcBlocksConfig } from './constants';

/**
 * Checks if experimental blocks are enabled. Do not use to conditionally register blocks,
 * use BlockTypesController to conditionally register blocks.
 *
 * @return {boolean} True if this experimental blocks are enabled.
 */
export const isExperimentalBlocksEnabled = (): boolean => {
	const { experimentalBlocksEnabled } = getSetting( 'wcBlocksConfig', {
		experimentalBlocksEnabled: false,
	} ) as WcBlocksConfig;

	return experimentalBlocksEnabled;
};

export const isExperimentalMiniCartEnabled = (): boolean => {
	const miniCartConfig = getConfig( 'woocommerce/mini-cart' ) as {
		experimentalMiniCartEnabled?: boolean;
	};
	return miniCartConfig?.experimentalMiniCartEnabled ?? false;
};
