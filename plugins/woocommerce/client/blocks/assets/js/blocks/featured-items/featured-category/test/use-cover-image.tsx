/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { registerCoreBlocks } from '@wordpress/block-library';
import {
	createBlock,
	createBlocksFromInnerBlocksTemplate,
	serialize,
} from '@wordpress/blocks';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import type { WP_REST_API_Category as Category } from 'wp-types';

/**
 * Internal dependencies
 */
import { FEATURED_CATEGORY_DEFAULT_TEMPLATE } from '../../constants';
import { useCoverImage } from '../use-cover-image';

beforeAll( () => registerCoreBlocks() );

it( 'keeps image-less categories blank, follows category images, and preserves manual replacements', () => {
	const empty = { id: 1, image: null } as unknown as Category;
	const pictured = {
		id: 2,
		image: { id: 42, src: 'https://example.com/category.jpg' },
	} as unknown as Category;
	const registry = createRegistry();
	registry.register( blockEditorStore );
	const [ cover ] = createBlocksFromInnerBlocksTemplate(
		FEATURED_CATEGORY_DEFAULT_TEMPLATE( empty )
	);
	const parent = createBlock( 'core/group', {}, [ cover ] );
	registry.dispatch( blockEditorStore ).resetBlocks( [ parent ] );
	const attributes = () =>
		registry.select( blockEditorStore ).getBlock( cover.clientId )
			?.attributes || {};
	const { rerender } = renderHook(
		( category ) => useCoverImage( parent.clientId, category ),
		{
			initialProps: empty,
			wrapper: ( { children } ) => (
				<RegistryProvider value={ registry }>
					{ children }
				</RegistryProvider>
			),
		}
	);
	expect( attributes().url ).toBe( '' );
	expect( serialize( cover ) ).not.toContain( '<img' );
	rerender( pictured );
	expect( attributes().url ).toBe( 'https://example.com/category.jpg' );
	rerender( empty );
	expect( attributes().url ).toBe( '' );
	expect(
		attributes().metadata[ 'woocommerce/featured-category-image' ].url
	).toBe( '' );
	act( () => {
		registry
			.dispatch( blockEditorStore )
			.updateBlockAttributes( cover.clientId, {
				id: 99,
				url: 'https://example.com/custom.jpg',
			} );
	} );
	rerender( pictured );
	expect( attributes().url ).toBe( 'https://example.com/custom.jpg' );
	expect( attributes().metadata ).not.toHaveProperty(
		'woocommerce/featured-category-image'
	);
} );
