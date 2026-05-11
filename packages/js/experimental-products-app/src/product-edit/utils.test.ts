/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import { productFields } from '../product-list/fields';
import {
	buildMergedProductEditData,
	EXCLUDED_PRODUCT_EDIT_FIELD_IDS,
	getProductWithUpdatedVariation,
	getProductEditFields,
	getProductVariationUpdatePath,
	getVisibleProductEditFields,
	isProductVariation,
} from './utils';

jest.mock( '@dnd-kit/react', () => ( {
	DragDropProvider: ( { children }: { children: React.ReactNode } ) =>
		children,
} ) );

jest.mock( '@dnd-kit/react/sortable', () => ( {
	isSortable: () => false,
	useSortable: () => ( {
		ref: () => undefined,
		handleRef: () => undefined,
		isDragging: false,
	} ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	CURRENCY: {
		code: 'USD',
		symbol: '$',
		symbolPosition: 'left',
		precision: 2,
	},
} ) );

describe( 'product edit utils', () => {
	const buildProduct = (
		overrides: Partial< ProductEntityRecord > = {}
	): ProductEntityRecord =>
		( {
			id: 12,
			name: 'Beanie',
			status: 'draft',
			type: 'simple',
			virtual: false,
			downloadable: false,
			on_sale: false,
			categories: [],
			tags: [],
			images: [],
			...overrides,
		} as unknown as ProductEntityRecord );

	it( 'returns the original values for a single selected product', () => {
		const product = buildProduct( {
			name: 'Hoodie',
			categories: [ { id: 15, name: 'Clothing' } ],
		} );

		expect( buildMergedProductEditData( [ product ] ) ).toEqual(
			expect.objectContaining( {
				name: 'Hoodie',
				categories: [ { id: 15, name: 'Clothing' } ],
			} )
		);
	} );

	it( 'preserves shared values in a bulk selection', () => {
		const products = [
			buildProduct( {
				id: 1,
				name: 'Beanie',
				status: 'publish',
			} ),
			buildProduct( {
				id: 2,
				name: 'Beanie',
				status: 'publish',
			} ),
		];

		expect( buildMergedProductEditData( products ) ).toEqual(
			expect.objectContaining( {
				name: 'Beanie',
				status: 'publish',
			} )
		);
	} );

	it( 'uses neutral empty values for mixed bulk field values', () => {
		const products = [
			buildProduct( {
				id: 1,
				name: 'Beanie',
				categories: [ { id: 15, name: 'Clothing' } ],
			} ),
			buildProduct( {
				id: 2,
				name: 'Hoodie',
				categories: [ { id: 22, name: 'Accessories' } ],
			} ),
		];

		expect( buildMergedProductEditData( products ) ).toEqual(
			expect.objectContaining( {
				name: '',
				categories: [],
			} )
		);
	} );

	it( 'excludes summary and count fields from the edit field list', () => {
		const editFieldIds = getProductEditFields( [
			{ id: 'name' },
			{ id: 'images_count' },
			{ id: 'price_summary' },
			{ id: 'linked_products_count' },
			{ id: 'sku' },
		] as Field< ProductEntityRecord >[] ).map( ( field ) => field.id );

		expect( editFieldIds ).not.toEqual(
			expect.arrayContaining( [
				'images_count',
				'price_summary',
				'inventory_summary',
				'organization_summary',
				'visibility_summary',
				'downloadable_count',
				'shipping_summary',
				'linked_products_count',
			] )
		);
	} );

	it( 'identifies variations and builds their update endpoint path', () => {
		const variation = buildProduct( {
			id: 34,
			parent_id: 12,
			type: 'variation',
		} );

		expect( isProductVariation( variation ) ).toBe( true );

		if ( isProductVariation( variation ) ) {
			expect( getProductVariationUpdatePath( variation ) ).toBe(
				'/wc/v3/products/12/variations/34'
			);
		}

		expect(
			isProductVariation( buildProduct( { id: 12, parent_id: 0 } ) )
		).toBe( false );
		expect(
			isProductVariation(
				buildProduct( {
					id: 34,
					parent_id: 0,
					type: 'variation',
				} )
			)
		).toBe( true );
		const orphanVariation = buildProduct( {
			id: 34,
			parent_id: 0,
			type: 'variation',
		} );

		if ( isProductVariation( orphanVariation ) ) {
			expect( () =>
				getProductVariationUpdatePath( orphanVariation )
			).toThrow( 'Variation parent ID is required' );
		}
	} );

	it( 'updates an embedded variation in a product record', () => {
		const variation = buildProduct( {
			id: 34,
			parent_id: 12,
			name: 'Blue',
			type: 'variation',
		} );
		const updatedVariation = {
			...variation,
			name: 'Green',
		};
		const parent = buildProduct( {
			id: 12,
			_embedded: {
				variations: [ variation ],
			},
		} );

		expect(
			getProductWithUpdatedVariation( parent, updatedVariation )
		).toEqual(
			expect.objectContaining( {
				id: 12,
				_embedded: {
					variations: [ updatedVariation ],
				},
			} )
		);
	} );

	describe( 'getVisibleProductEditFields', () => {
		const getVisibleFieldIds = ( products: ProductEntityRecord[] ) =>
			getVisibleProductEditFields(
				getProductEditFields( productFields ),
				products
			).map( ( field ) => field.id );

		const getVisibleField = (
			products: ProductEntityRecord[],
			fieldId: string
		) =>
			getVisibleProductEditFields(
				getProductEditFields( productFields ),
				products
			).find( ( field ) => field.id === fieldId );

		const expectFieldsHidden = (
			fieldIds: string[],
			hiddenFieldIds: string[]
		) => {
			hiddenFieldIds.forEach( ( fieldId ) => {
				expect( fieldIds ).not.toContain( fieldId );
			} );
		};
		const parentOwnedFieldIds = [
			'name',
			'short_description',
			'description',
			'product_status',
			'catalog_visibility',
			'categories',
			'tags',
			'type',
			'featured',
			'upsell_ids',
			'cross_sell_ids',
			'external_url',
			'button_text',
		];
		const priceFieldIds = [
			'price',
			'regular_price',
			'on_sale',
			'sale_price',
			'schedule_sale',
			'date_on_sale_from',
			'date_on_sale_to',
		];
		const universalFieldIds = [
			'images',
			'sku',
			'manage_stock',
			'stock_quantity',
			'weight',
			'length',
			'width',
			'height',
			'shipping_class',
			'tax_status',
		];

		it( 'shows pricing, shipping, and linked product fields for simple physical products', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'simple',
					virtual: false,
					downloadable: false,
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'price',
					'regular_price',
					'on_sale',
					'sale_price',
					'schedule_sale',
					'date_on_sale_from',
					'weight',
					'length',
					'width',
					'height',
					'shipping_class',
					'upsell_ids',
					'cross_sell_ids',
				] )
			);
			expectFieldsHidden( fieldIds, [ 'external_url', 'button_text' ] );
		} );

		it( 'does not include excluded fields in product type compatibility', () => {
			const fieldIds = getVisibleProductEditFields( productFields, [
				buildProduct( {
					type: 'simple',
				} ),
			] ).map( ( field ) => field.id );

			expect( fieldIds ).not.toEqual(
				expect.arrayContaining( [ ...EXCLUDED_PRODUCT_EDIT_FIELD_IDS ] )
			);
		} );

		it( 'uses the same compatible fields for simple products regardless of virtual status', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'simple',
					virtual: true,
					downloadable: false,
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'price',
					'regular_price',
					'weight',
					'length',
					'width',
					'height',
					'shipping_class',
					'upsell_ids',
					'cross_sell_ids',
				] )
			);
			expectFieldsHidden( fieldIds, [ 'external_url', 'button_text' ] );
		} );

		it( 'shows downloads for simple downloadable products', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'simple',
					virtual: false,
					downloadable: true,
				} ),
			] );

			expect( fieldIds ).toContain( 'downloadable' );
			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'weight',
					'length',
					'width',
					'height',
					'shipping_class',
				] )
			);
		} );

		it( 'shows external fields for external products', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'external',
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'price',
					'regular_price',
					'external_url',
					'button_text',
					'upsell_ids',
				] )
			);
			expectFieldsHidden( fieldIds, [
				'cross_sell_ids',
				'downloadable',
				'weight',
				'length',
				'width',
				'height',
				'shipping_class',
			] );
		} );

		it( 'hides parent pricing and downloads for variable products', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'variable',
					manage_stock: true,
				} ),
			] );

			expectFieldsHidden( fieldIds, [
				'price',
				'regular_price',
				'on_sale',
				'sale_price',
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
				'downloadable',
			] );
			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'upsell_ids',
					'cross_sell_ids',
					...universalFieldIds,
				] )
			);
		} );

		it( 'shows parent-owned and universal fields for simple and variable products', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 1,
					type: 'simple',
					virtual: false,
					downloadable: false,
					manage_stock: true,
				} ),
				buildProduct( {
					id: 2,
					type: 'variable',
					manage_stock: true,
				} ),
			] );

			expectFieldsHidden( fieldIds, priceFieldIds );
			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'name',
					'product_status',
					'catalog_visibility',
					'categories',
					'tags',
					'featured',
					'upsell_ids',
					'cross_sell_ids',
					...universalFieldIds,
				] )
			);
		} );

		it( 'shows sellable instance fields for variations', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					manage_stock: true,
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					...priceFieldIds,
					...universalFieldIds,
				] )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				'downloadable',
			] );
		} );

		it( 'shows shared sellable instance fields for simple products and variations', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 1,
					type: 'simple',
					manage_stock: true,
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					manage_stock: true,
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					...priceFieldIds,
					...universalFieldIds,
				] )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				'downloadable',
			] );
		} );

		it( 'shows only universal fields for variable products and variations', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 12,
					type: 'variable',
					manage_stock: true,
				} ),
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					manage_stock: true,
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( universalFieldIds )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				...priceFieldIds,
				'downloadable',
			] );
		} );

		it( 'shows only universal fields for simple, variable, and variation selections', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 1,
					type: 'simple',
					manage_stock: true,
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
				buildProduct( {
					id: 12,
					type: 'variable',
					manage_stock: true,
				} ),
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					manage_stock: true,
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( universalFieldIds )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				...priceFieldIds,
				'downloadable',
			] );
		} );

		it( 'does not return visibility predicates after checking selected products', () => {
			const field = getVisibleField(
				[
					buildProduct( {
						id: 1,
						type: 'simple',
						on_sale: true,
					} ),
					buildProduct( {
						id: 2,
						type: 'simple',
						sale_price: '12',
					} ),
				],
				'sale_price'
			);

			expect( field ).toBeDefined();
			expect( field?.isVisible ).toBeUndefined();
		} );
	} );
} );
