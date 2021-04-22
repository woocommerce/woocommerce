/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { WC_BLOCKS_PHASE } from './constants';

/**
 * Registers a new experimental block provided a unique name and an object defining its
 * behavior. Once registered, the block is made available as an option to any
 * editor interface where blocks are implemented.
 */
export const registerExperimentalBlockType = (
	name: string,
	settings: Record< string, unknown >
): Record< string, unknown > | undefined => {
	if ( WC_BLOCKS_PHASE > 2 ) {
		return registerBlockType( name, settings );
	}
};

/**
 * Registers a new feature plugin block provided a unique name and an object
 * defining its behavior. Once registered, the block is made available as an
 * option to any editor interface where blocks are implemented.
 */
export const registerFeaturePluginBlockType = (
	name: string,
	settings: Record< string, unknown >
): Record< string, unknown > | undefined => {
	if ( WC_BLOCKS_PHASE > 1 ) {
		return registerBlockType( name, settings );
	}
};

/**
 * Checks if we're executing the code in an experimental build mode.
 *
 * @return {boolean} True if this is an experimental build, false otherwise.
 */
export const isExperimentalBuild = (): boolean => WC_BLOCKS_PHASE > 2;

/**
 * Checks if we're executing the code in an feature plugin or experimental build mode.
 *
 * @return {boolean} True if this is an experimental or feature plugin build, false otherwise.
 */
export const isFeaturePluginBuild = (): boolean => WC_BLOCKS_PHASE > 1;
