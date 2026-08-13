/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

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

export const isExperimentalWcRestApiV4Enabled = (): boolean => {
	const experimentalWcRestApiV4 = getSetting(
		'experimentalWcRestApiV4',
		false
	);
	return experimentalWcRestApiV4;
};
