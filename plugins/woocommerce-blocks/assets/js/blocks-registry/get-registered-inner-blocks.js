/**
 * Internal dependencies
 */
import { registeredBlocks } from './registered-blocks-init';

/**
 * Retrieves the inner blocks registered as a child of a specific one.
 *
 * @export
 * @param {string}   main Name of the parent block to retrieve children of.
 *
 * @returns {Object}
 */
export function getRegisteredInnerBlocks( main ) {
	return typeof registeredBlocks[ main ] === 'object' &&
		Object.keys( registeredBlocks[ main ] ).length > 0
		? registeredBlocks[ main ]
		: {};
}
