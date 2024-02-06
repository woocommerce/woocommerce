/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import blockEditWithBoundAttribute from './block-edit-with-binding-attrs';

type BLOCK_BINDINGS_ALLOWED_BLOCKS_TYPE = {
	[ key: string ]: string[];
};

export const BLOCK_BINDINGS_ALLOWED_BLOCKS: BLOCK_BINDINGS_ALLOWED_BLOCKS_TYPE =
	{
		'core/paragraph': [ 'content' ],
		'core/heading': [ 'content' ],
		'core/image': [ 'url', 'title', 'alt' ],
		'core/button': [ 'url', 'text', 'linkTarget' ],
		'woocommerce/product-text-area-field': [ 'content', 'placeholder' ],
	};

export function isBlockAllowed( blockName: string ): boolean {
	return blockName in BLOCK_BINDINGS_ALLOWED_BLOCKS;
}

export default function extendBlockWithBoundAttributes(
	settings: BlockConfiguration,
	name: string
): BlockConfiguration {
	if ( ! isBlockAllowed( name ) ) {
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
			? blockEditWithBoundAttribute( settings.edit )
			: undefined,
	};
}
