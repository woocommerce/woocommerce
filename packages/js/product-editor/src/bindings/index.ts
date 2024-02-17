/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { dispatch } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
/**
 * Internal dependencies
 */
import extendBlockWithBoundAttributes from './extend-blocks-with-bound-attributes';
import type { BindingSourceHandlerProps } from './types';
import wooEntitySource from '../bindings-sources/entity-source';
import { type WooEntitySourceArgs } from '../bindings-sources/entity-source/types';

type BLOCK_BINDINGS_ALLOWED_BLOCKS_TYPE = {
	[ key: string ]: string[];
};

export const BLOCK_BINDINGS_ALLOWED_BLOCKS: BLOCK_BINDINGS_ALLOWED_BLOCKS_TYPE =
	{
		'core/paragraph': [ 'content' ],
		'core/heading': [ 'content' ],
		'core/image': [ 'url', 'title', 'alt' ],
		'core/button': [ 'url', 'text', 'linkTarget' ],
		'woocommerce/product-text-field': [ 'value' ],
	};

/**
 * Based on the given block name,
 * check if it is possible to bind the block.
 *
 * @param {string} blockName - The block name.
 * @return {boolean} Whether it is possible to bind the block.
 */
export function isItPossibleToBindBlock( blockName: string ): boolean {
	return blockName in BLOCK_BINDINGS_ALLOWED_BLOCKS;
}

/**
 * Based on the given block name and attribute name,
 * check if it is possible to bind the block.
 *
 * @param {string} blockName     - The block name.
 * @param {string} attributeName - The attribute name.
 * @return {boolean} Whether it is possible to bind the block.
 */
export function hasPossibleBlockBinding(
	blockName: string,
	attributeName: string
): boolean {
	return (
		isItPossibleToBindBlock( blockName ) &&
		BLOCK_BINDINGS_ALLOWED_BLOCKS[ blockName ].includes( attributeName )
	);
}

const sourceHandlers = {
	[ wooEntitySource.name ]: wooEntitySource,
};

export function getBlockBindingsSource(
	source: string
): BindingSourceHandlerProps< WooEntitySourceArgs > {
	return sourceHandlers[ source ];
}

const registerBlockBindingsSource =
	// @ts-expect-error There are no types for this.
	dispatch( blockEditorStore )?.registerBlockBindingsSource;

/**
 * Check if the block binding API is available.
 * Todo: polish the conditions to check if the API is available.
 *
 * @return {boolean} Whether the block binding API is available.
 */
export function isBlockBindingAPIAvailable(): boolean {
	const isAvailable = Boolean( registerBlockBindingsSource );
	if ( ! isAvailable ) {
		console.warn( 'Binding API not available' ); // eslint-disable-line no-console
	}
	return isAvailable;
}

export default function bindEditorBlocks() {
	if ( ! isBlockBindingAPIAvailable() ) {
		/*
		 * Fallback to extending blocks with bound attributes.
		 */
		addFilter(
			'blocks.registerBlockType',
			'woocommerce/product-editor/block-edit-with-binding-attributes',
			extendBlockWithBoundAttributes
		);

		return;
	}

	// Register source handlers
	for ( const sourceHandler of Object.values( sourceHandlers ) ) {
		registerBlockBindingsSource( sourceHandler );
	}
}
