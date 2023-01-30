/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { WOOCOMMERCE_BLOCKS_PHASE } from './constants';

/**
 * Registers a new experimental block provided a unique name and an object defining its
 * behavior. Once registered, the block is made available as an option to any
 * editor interface where blocks are implemented.
 *
 * @param {string} name     Block name.
 * @param {Object} settings Block settings.
 *
 * @return {?Object} The block, if it has been successfully registered;
 *                    otherwise `undefined`.
 */
export const registerExperimentalBlockType = ( name, settings ) => {
	if ( WOOCOMMERCE_BLOCKS_PHASE > 2 ) {
		return registerBlockType( name, settings );
	}
};

/**
 * Registers a new feature plugin block provided a unique name and an object
 * defining its behavior. Once registered, the block is made available as an
 * option to any editor interface where blocks are implemented.
 *
 * @param {string} name     Block name.
 * @param {Object} settings Block settings.
 *
 * @return {?Object} The block, if it has been successfully registered;
 *                    otherwise `undefined`.
 */
export const registerFeaturePluginBlockType = ( name, settings ) => {
	if ( WOOCOMMERCE_BLOCKS_PHASE > 1 ) {
		return registerBlockType( name, settings );
	}
};

/**
 * Checks if we're executing the code in an experimental build mode.
 *
 * @return {boolean} True if this is an experimental build, false otherwise.
 */
export const isExperimentalBuild = () => WOOCOMMERCE_BLOCKS_PHASE > 2;

/**
 * Checks if we're executing the code in an feature plugin or experimental build mode.
 *
 * @return {boolean} True if this is an experimental or feature plugin build, false otherwise.
 */
export const isFeaturePluginBuild = () => WOOCOMMERCE_BLOCKS_PHASE > 1;
