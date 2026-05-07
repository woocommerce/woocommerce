/**
 * External dependencies
 */
import type { ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { normalizeVariation } from './normalization';

describe( 'normalizeVariation', () => {
	it( 'maps variation image and product fields for reusable field renderers', () => {
		const variation = {
			id: 11,
			parent_id: 99,
			name: '',
			attributes: [
				{
					id: 1,
					name: 'Color',
					slug: 'pa_color',
					option: 'Blue',
				},
			],
			image: {
				id: 5,
				src: 'https://example.com/blue.jpg',
				name: 'Blue image',
				alt: 'Blue',
			},
			manage_stock: 'parent',
		} as ProductVariation;

		const normalized = normalizeVariation( variation );

		expect( normalized.name ).toBe( 'Blue' );
		expect( normalized.type ).toBe( 'variation' );
		expect( normalized.categories ).toEqual( [] );
		expect( normalized.tags ).toEqual( [] );
		expect( normalized.manage_stock ).toBe( false );
		expect( normalized.images ).toEqual( [
			expect.objectContaining( {
				id: 5,
				src: 'https://example.com/blue.jpg',
				alt: 'Blue',
				name: 'Blue image',
			} ),
		] );
	} );

	it( 'falls back to the variation ID when there are no attributes', () => {
		const normalized = normalizeVariation( {
			id: 12,
			parent_id: 99,
			name: '',
			attributes: [],
			manage_stock: false,
		} as unknown as ProductVariation );

		expect( normalized.name ).toBe( 'Variation #12' );
	} );
} );
