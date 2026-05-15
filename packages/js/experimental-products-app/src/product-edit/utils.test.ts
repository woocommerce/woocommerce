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
	getProductEditRecord,
	getProductVariationUpdatePath,
	getProductTypeFormFields,
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

	it( 'uses edited product values over the listed product values', () => {
		const listedProduct = buildProduct( {
			id: 12,
			name: 'Beanie',
			on_sale: false,
			regular_price: '15',
			categories: [ { id: 22, name: 'Accessories' } ],
		} );
		const editedProduct = {
			on_sale: true,
			sale_price: '12',
		};

		expect(
			getProductEditRecord( listedProduct, undefined, editedProduct )
		).toEqual(
			expect.objectContaining( {
				on_sale: true,
				sale_price: '12',
				regular_price: '15',
				categories: [ { id: 22, name: 'Accessories' } ],
			} )
		);
	} );

	it( 'falls back to the listed product when the root record is unavailable', () => {
		const listedProduct = buildProduct( {
			id: 12,
			name: 'Beanie',
		} );

		expect( getProductEditRecord( listedProduct, false ) ).toBe(
			listedProduct
		);
		expect( getProductEditRecord( listedProduct, undefined ) ).toBe(
			listedProduct
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
		const expectFieldOrder = (
			fieldIds: string[],
			orderedFieldIds: string[]
		) => {
			orderedFieldIds.forEach( ( fieldId, index ) => {
				const previousFieldId = orderedFieldIds[ index - 1 ];

				if ( previousFieldId ) {
					expect( fieldIds.indexOf( previousFieldId ) ).toBeLessThan(
						fieldIds.indexOf( fieldId )
					);
				}
			} );
		};
		const parentOwnedFieldIds = [
			'name',
			'short_description',
			'description',
			'catalog_visibility',
			'categories',
			'brands',
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
			'sale_price',
			'schedule_sale',
			'date_on_sale_from',
			'date_on_sale_to',
		];
		const basePriceFieldIds = [ 'regular_price', 'sale_price' ];
		const managedStockFieldIds = [ 'manage_stock', 'stock_quantity' ];
		const stockStatusFieldIds = [ 'stock', 'manage_stock' ];
		const shippingFieldIds = [
			'weight',
			'length',
			'width',
			'height',
			'shipping_class',
		];
		const sellableInstanceFieldIds = [
			'images',
			'sku',
			...managedStockFieldIds,
		];
		const bulkSellableInstanceFieldIds = sellableInstanceFieldIds.filter(
			( fieldId ) => fieldId !== 'sku'
		);

		it( 'shows simple product fields in quick edit order', () => {
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

			expect( fieldIds ).toEqual( [
				'name',
				'product_status',
				'catalog_visibility',
				'regular_price',
				'sale_price',
				'images',
				'sku',
				'stock',
				'manage_stock',
				'categories',
				'brands',
				'tags',
				'featured',
				'weight',
				'length',
				'width',
				'height',
			] );
			expectFieldsHidden( fieldIds, [
				'price',
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
				'downloadable',
				'external_url',
				'button_text',
				'shipping_class',
				'tax_status',
				'upsell_ids',
				'cross_sell_ids',
			] );
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

		it( 'orders pricing fields for the quick edit form', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'simple',
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
			] );

			expectFieldOrder( fieldIds, [ 'regular_price', 'sale_price' ] );
		} );

		it( 'hides shipping fields for virtual simple products', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'simple',
					virtual: true,
					downloadable: false,
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'regular_price',
					'categories',
					'brands',
					'tags',
				] )
			);
			expectFieldsHidden( fieldIds, [
				...shippingFieldIds,
				'external_url',
				'button_text',
				'upsell_ids',
				'cross_sell_ids',
			] );
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
				] )
			);
			expectFieldOrder( fieldIds, [ 'images', 'downloadable', 'sku' ] );
			expectFieldsHidden( fieldIds, [ 'shipping_class' ] );
		} );

		it( 'shows grouped product fields in quick edit order', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'grouped',
				} ),
			] );

			expect( fieldIds ).toEqual( [
				'name',
				'product_status',
				'catalog_visibility',
				'upsell_ids',
				'images',
				'sku',
				'categories',
				'brands',
				'tags',
				'featured',
			] );
			expectFieldsHidden( fieldIds, [
				...priceFieldIds,
				'downloadable',
				'cross_sell_ids',
				'external_url',
				'button_text',
				...shippingFieldIds,
				...stockStatusFieldIds,
				'stock_quantity',
				'tax_status',
			] );
		} );

		it( 'shows external fields for external products', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'external',
				} ),
			] );

			expect( fieldIds ).toEqual( [
				'name',
				'product_status',
				'catalog_visibility',
				'regular_price',
				'sale_price',
				'images',
				'external_url',
				'button_text',
				'sku',
				'categories',
				'brands',
				'tags',
				'featured',
			] );
			expectFieldsHidden( fieldIds, [
				'price',
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
				'cross_sell_ids',
				'downloadable',
				'upsell_ids',
				...shippingFieldIds,
				...stockStatusFieldIds,
				'stock_quantity',
			] );
		} );

		it( 'shows variable parent fields in quick edit order', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'variable',
				} ),
			] );

			expectFieldsHidden( fieldIds, [
				'price',
				'regular_price',
				'sale_price',
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
				'downloadable',
			] );
			expect( fieldIds ).toEqual( [
				'name',
				'product_status',
				'catalog_visibility',
				'images',
				'sku',
				'manage_stock',
				'stock',
				'categories',
				'brands',
				'tags',
				'featured',
				'shipping_class',
				'length',
				'width',
				'height',
				'weight',
			] );
			expectFieldsHidden( fieldIds, [
				'short_description',
				'description',
				'stock_quantity',
				'tax_status',
				'upsell_ids',
				'cross_sell_ids',
			] );
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
			expectFieldsHidden( fieldIds, [ 'sku' ] );
			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'name',
					'product_status',
					'catalog_visibility',
					'categories',
					'brands',
					'tags',
					'featured',
					'images',
					'manage_stock',
					'weight',
					'length',
					'width',
					'height',
				] )
			);
			expectFieldsHidden( fieldIds, [
				'upsell_ids',
				'cross_sell_ids',
				'shipping_class',
				'tax_status',
				'stock_quantity',
			] );
		} );

		it( 'shows sale fields but not SKU when bulk editing simple products', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 1,
					type: 'simple',
					regular_price: '12',
					price: '12',
					on_sale: false,
				} ),
				buildProduct( {
					id: 2,
					type: 'simple',
					regular_price: '15',
					price: '15',
					on_sale: true,
					sale_price: '12',
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( basePriceFieldIds )
			);
			expectFieldsHidden( fieldIds, [
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
			] );
			expectFieldsHidden( fieldIds, [ 'sku' ] );
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
					'product_status',
					'regular_price',
					'sale_price',
					'images',
					'sku',
					'manage_stock',
					'stock_quantity',
					'shipping_class',
					'weight',
					'length',
					'width',
					'height',
				] )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				'stock',
				'downloadable',
				'price',
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
				'tax_status',
			] );
		} );

		it( 'computes variation fields from parent IDs even when type differs', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'simple',
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'images',
					'sku',
					'regular_price',
					'sale_price',
					'stock',
					'manage_stock',
					'product_status',
					'shipping_class',
					'weight',
					'length',
					'width',
					'height',
				] )
			);
			expectFieldsHidden( fieldIds, parentOwnedFieldIds );
			expectFieldsHidden( fieldIds, [
				'price',
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
				'tax_status',
			] );
		} );

		it( 'hides shipping fields for virtual variations', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					virtual: true,
					downloadable: true,
				} ),
			] );

			expectFieldsHidden( fieldIds, [ 'downloadable' ] );
			expectFieldsHidden( fieldIds, shippingFieldIds );
		} );

		it( 'shows shipping and dimensions for physical variations', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					virtual: false,
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'shipping_class',
					'weight',
					'length',
					'width',
					'height',
				] )
			);
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
					'regular_price',
					'sale_price',
					...bulkSellableInstanceFieldIds,
				] )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				'downloadable',
				'sku',
				'price',
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
			] );
		} );

		it( 'hides shipping fields when a bulk variation selection includes virtual items', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 1,
					type: 'simple',
					virtual: false,
					manage_stock: true,
				} ),
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					virtual: true,
					manage_stock: true,
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					...basePriceFieldIds,
					'images',
					...managedStockFieldIds,
				] )
			);
			expectFieldsHidden( fieldIds, [
				...shippingFieldIds,
				'tax_status',
				'sku',
			] );
		} );

		it( 'hides downloadable fields for simple product and variation selections', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 1,
					type: 'simple',
					downloadable: true,
				} ),
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					downloadable: true,
				} ),
			] );

			expectFieldsHidden( fieldIds, [ 'downloadable' ] );
		} );

		it( 'hides downloadable fields unless every bulk item supports downloads', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					id: 1,
					type: 'simple',
					downloadable: true,
				} ),
				buildProduct( {
					id: 2,
					type: 'simple',
					downloadable: false,
				} ),
			] );

			expectFieldsHidden( fieldIds, [ 'downloadable' ] );
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
				expect.arrayContaining( [
					'product_status',
					'images',
					'manage_stock',
					'shipping_class',
					'weight',
					'length',
					'width',
					'height',
				] )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				...priceFieldIds,
				'downloadable',
				'sku',
				'stock',
				'stock_quantity',
				'tax_status',
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
				expect.arrayContaining( [
					'product_status',
					'images',
					'manage_stock',
					'weight',
					'length',
					'width',
					'height',
				] )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				...priceFieldIds,
				'downloadable',
				'sku',
				'stock',
				'stock_quantity',
				'shipping_class',
				'tax_status',
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

	describe( 'getProductTypeFormFields', () => {
		it( 'uses simple product form config with height last', () => {
			const product = buildProduct( {
				type: 'simple',
				virtual: false,
			} );

			expect( getProductTypeFormFields( [ product ] ) ).toEqual( [
				'name',
				'product_status',
				'catalog_visibility',
				'regular_price',
				'sale_price',
				'images',
				'downloadable',
				'sku',
				'stock',
				'manage_stock',
				'stock_quantity',
				'categories',
				'brands',
				'tags',
				'featured',
				{
					id: 'dimensions',
					layout: { type: 'row' },
					children: [ 'weight', 'length', 'width' ],
				},
				'height',
			] );
		} );

		it( 'uses variable parent form config in design order', () => {
			const product = buildProduct( {
				type: 'variable',
				virtual: false,
			} );

			expect( getProductTypeFormFields( [ product ] ) ).toEqual( [
				'name',
				'product_status',
				'catalog_visibility',
				'images',
				'sku',
				'manage_stock',
				'stock',
				'categories',
				'brands',
				'tags',
				'featured',
				'shipping_class',
				{
					id: 'parent-dimensions',
					layout: { type: 'row' },
					children: [ 'length', 'width', 'height' ],
				},
				'weight',
			] );
		} );

		it( 'uses variation product form config', () => {
			const product = buildProduct( {
				id: 34,
				parent_id: 12,
				type: 'variation',
				virtual: false,
				downloadable: true,
			} );

			expect( getProductTypeFormFields( [ product ] ) ).toEqual( [
				'product_status',
				'regular_price',
				'sale_price',
				'images',
				'sku',
				'manage_stock',
				'stock',
				'stock_quantity',
				'shipping_class',
				{
					id: 'dimensions',
					layout: { type: 'row' },
					children: [ 'weight', 'length', 'width' ],
				},
				'height',
			] );
		} );
	} );
} );
