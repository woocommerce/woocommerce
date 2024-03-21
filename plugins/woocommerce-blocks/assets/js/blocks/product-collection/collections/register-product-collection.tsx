/**
 * External dependencies
 */
import { BlockVariation, registerBlockVariation } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { EditorBlock } from '@woocommerce/types';
import { type ElementType } from '@wordpress/element';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import blockJson from '../block.json';
import { HandlePreviewStateArgs, ProductCollectionAttributes } from '../types';

// Extends BlockVariation to include an optional handlePreviewState function for preview management.
interface ProductCollectionConfig extends BlockVariation {
	handlePreviewState?: HandlePreviewStateArgs;
}

// Stores callback functions for handling the preview state of product collections, indexed by collection name.
const registeredPreviewModeCallbacks: {
	[ key: string ]: HandlePreviewStateArgs;
} = {};

/**
 * Registers a product collection variation, optionally setting up a preview state handler.
 *
 * @param {ProductCollectionConfig} blockVariationArgs - The configuration for the product collection, potentially including a handlePreviewState function.
 */
const registerProductCollection = ( {
	handlePreviewState,
	...blockVariationArgs
}: ProductCollectionConfig ) => {
	if ( handlePreviewState ) {
		// Registers a callback for managing the preview state if provided.
		registeredPreviewModeCallbacks[ blockVariationArgs.name ] =
			handlePreviewState;
	}

	registerBlockVariation( blockJson.name, {
		...blockVariationArgs,
	} );
};

/**
 * Wraps the BlockEdit component to inject preview state management capabilities.
 * This higher-order component adds a handlePreviewState prop to BlockEdit if a preview state callback is registered for the block's collection.
 */
const withHandlePreviewState =
	< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
	( props: BlockEditProps< ProductCollectionAttributes > ) => {
		// Retrieves the callback for the current block's collection, if any.
		const registeredCallback =
			registeredPreviewModeCallbacks[
				props?.attributes?.collection as string
			];

		// If there is no registered callback for preview state management, render the block edit component as usual.
		if ( ! registeredCallback ) return <BlockEdit { ...props } />;

		// Otherwise, inject the handlePreviewState function into the BlockEdit component's props.
		return (
			<BlockEdit { ...props } handlePreviewState={ registeredCallback } />
		);
	};
addFilter( 'editor.BlockEdit', blockJson.name, withHandlePreviewState );

export default registerProductCollection;
