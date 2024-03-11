/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { isItPossibleToBindBlock } from '..';
import withBlockBindingSupport from './core-block-binding-support';

/**
 * Extend the block settings with the bound attributes.
 *
 * @param {BlockConfiguration} settings - The block settings.
 * @param {string}             name     - The block name.
 * @return {BlockConfiguration} The extended block settings.
 */
export default function extendBlockWithBoundAttributes(
	settings: BlockConfiguration,
	name: string
): BlockConfiguration {
	if ( ! isItPossibleToBindBlock( name ) ) {
		return settings;
	}

	return {
		...settings,
		/*
		 * Expose relevant data through
		 * the block context.
		 */
		usesContext: [
			...new Set( [
				...( settings.usesContext || [] ),
				'postId',
				'postType',
				'queryId',
			] ),
		],
		edit: settings?.edit
			? withBlockBindingSupport( settings.edit )
			: undefined,
	};
}
