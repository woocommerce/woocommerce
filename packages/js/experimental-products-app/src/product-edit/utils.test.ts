/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { getSetting } from '@woocommerce/settings';

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
import {
	buildProductBulkEditData,
	getBulkNumericEditsFromData,
	getBulkNumericOperationFieldId,
	getBulkNumericChangesForProduct,
	validateBulkNumericEdits,
} from './bulk-edit';

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
	getSetting: jest.fn(),
} ) );

describe( 'product edit utils', () => {
	const getSettingMock = getSetting as jest.Mock;
	const mockCostOfGoodsSoldFeatureEnabled = ( isEnabled: boolean ) => {
		getSettingMock.mockImplementation( ( name, fallback ) =>
			name === 'admin'
				? {
						features: {
							cost_of_goods_sold: {
								is_enabled: isEnabled,
							},
						},
				  }
				: fallback
		);
	};
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
		} ) as unknown as ProductEntityRecord;
	const buildCostOfGoodsSold = (
		definedValue: number | string | null = 5
	): ProductEntityRecord[ 'cost_of_goods_sold' ] => ( {
		values: [
			{
				defined_value: definedValue,
				effective_value: definedValue,
			},
		],
		total_value: definedValue,
	} );

	beforeEach( () => {
		mockCostOfGoodsSoldFeatureEnabled( true );
	} );

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

	it( 'merges bulk dimensions per dimension field', () => {
		const products = [
			buildProduct( {
				id: 1,
				dimensions: {
					length: '12',
					width: '4',
					height: '3',
				},
			} ),
			buildProduct( {
				id: 2,
				dimensions: {
					length: '12',
					width: '7',
					height: '3',
				},
			} ),
		];
		const bulkData = buildProductBulkEditData(
			products,
			getProductEditFields( productFields )
		);

		expect( buildMergedProductEditData( products ) ).toEqual(
			expect.objectContaining( {
				dimensions: {
					length: '12',
					width: '',
					height: '3',
				},
			} )
		);
		expect( bulkData.fieldStates.length ).toEqual( {
			isEmpty: false,
			isMixed: false,
			placeholder: undefined,
			value: '12',
		} );
		expect( bulkData.fieldStates.width ).toEqual( {
			isEmpty: false,
			isMixed: true,
			placeholder: 'Mixed',
			value: undefined,
		} );
		expect( bulkData.fieldStates.height ).toEqual( {
			isEmpty: false,
			isMixed: false,
			placeholder: undefined,
			value: '3',
		} );
	} );

	it( 'returns bulk field state for mixed values', () => {
		const products = [
			buildProduct( {
				id: 1,
				name: 'Beanie',
				status: 'publish',
				weight: '1',
			} ),
			buildProduct( {
				id: 2,
				name: 'Hoodie',
				status: 'draft',
				weight: '2',
			} ),
		];

		const bulkData = buildProductBulkEditData(
			products,
			getProductEditFields( productFields )
		);

		expect( bulkData.data.name ).toBe( '' );
		expect( bulkData.fieldStates.name ).toEqual( {
			isEmpty: false,
			isMixed: true,
			placeholder: 'Mixed',
			value: undefined,
		} );
		expect( bulkData.fieldStates.product_status ).toEqual( {
			isEmpty: false,
			isMixed: true,
			placeholder: 'Mixed',
			value: undefined,
		} );
		expect( bulkData.fieldStates.weight ).toEqual( {
			isEmpty: false,
			isMixed: true,
			placeholder: 'Mixed',
			value: undefined,
		} );
	} );

	it( 'returns bulk field state for variation active values', () => {
		const activeVariation = buildProduct( {
			id: 1,
			parent_id: 12,
			type: 'variation',
			status: 'publish',
			price: '12',
		} );
		const inactiveVariation = buildProduct( {
			id: 2,
			parent_id: 12,
			type: 'variation',
			status: 'private',
			price: '12',
		} );

		expect(
			buildProductBulkEditData(
				[ activeVariation ],
				getProductEditFields( productFields )
			).fieldStates.variation_active
		).toEqual( {
			isEmpty: false,
			isMixed: false,
			placeholder: undefined,
			value: 'active',
		} );

		expect(
			buildProductBulkEditData(
				[ activeVariation, inactiveVariation ],
				getProductEditFields( productFields )
			).fieldStates.variation_active
		).toEqual( {
			isEmpty: false,
			isMixed: true,
			placeholder: 'Mixed',
			value: undefined,
		} );
	} );

	it( 'returns a mixed bulk field state for different grouped products', () => {
		const products = [
			buildProduct( {
				id: 1,
				type: 'grouped',
				grouped_products: [ 10, 11 ],
			} ),
			buildProduct( {
				id: 2,
				type: 'grouped',
				grouped_products: [ 12 ],
			} ),
		];

		const bulkData = buildProductBulkEditData(
			products,
			getProductEditFields( productFields )
		);

		expect( bulkData.data.grouped_products ).toEqual( [] );
		expect( bulkData.fieldStates.grouped_products ).toEqual( {
			isEmpty: false,
			isMixed: true,
			placeholder: 'Mixed',
			value: undefined,
		} );
	} );

	it( 'returns bulk field state for shared and empty values', () => {
		const products = [
			buildProduct( {
				id: 1,
				name: 'Beanie',
				regular_price: '',
			} ),
			buildProduct( {
				id: 2,
				name: 'Beanie',
				regular_price: '',
			} ),
		];

		const bulkData = buildProductBulkEditData(
			products,
			getProductEditFields( productFields )
		);

		expect( bulkData.fieldStates.name ).toEqual( {
			isEmpty: false,
			isMixed: false,
			placeholder: undefined,
			value: 'Beanie',
		} );
		expect( bulkData.fieldStates.regular_price ).toEqual( {
			isEmpty: true,
			isMixed: false,
			placeholder: undefined,
			value: '',
		} );
	} );

	describe( 'getBulkNumericChangesForProduct', () => {
		it( 'returns no edits for the don’t change operation', () => {
			expect(
				getBulkNumericChangesForProduct(
					buildProduct( { regular_price: '10' } ),
					{
						regular_price: {
							operation: 'dont_change',
							value: '',
						},
					}
				)
			).toEqual( {} );
		} );

		it( 'reads numeric edits from injected bulk operation fields', () => {
			expect(
				getBulkNumericEditsFromData( {
					[ getBulkNumericOperationFieldId( 'regular_price' ) ]:
						'increase',
					regular_price: '5',
					[ getBulkNumericOperationFieldId( 'stock_quantity' ) ]:
						'set',
					stock_quantity: 12,
					cost_of_goods_sold: buildCostOfGoodsSold( '7' ),
				} as unknown as ProductEntityRecord )
			).toEqual(
				expect.objectContaining( {
					regular_price: {
						operation: 'increase',
						value: '5',
					},
					stock_quantity: {
						operation: 'set',
						value: '12',
					},
					cost_of_goods_sold: {
						operation: 'dont_change',
						value: '7',
					},
				} )
			);
		} );

		it( 'sets, increases, and decreases money values', () => {
			const product = buildProduct( { regular_price: '10' } );

			expect(
				getBulkNumericChangesForProduct( product, {
					regular_price: { operation: 'set', value: '12' },
				} )
			).toEqual( { regular_price: '12.00' } );
			expect(
				getBulkNumericChangesForProduct( product, {
					regular_price: { operation: 'increase', value: '5' },
				} )
			).toEqual( { regular_price: '15.00' } );
			expect(
				getBulkNumericChangesForProduct( product, {
					regular_price: { operation: 'decrease', value: '20' },
				} )
			).toEqual( { regular_price: '0.00' } );
		} );

		it( 'applies percentage operations to money values', () => {
			const product = buildProduct( { sale_price: '20' } );

			expect(
				getBulkNumericChangesForProduct( product, {
					sale_price: {
						operation: 'increase_percent',
						value: '10',
					},
				} )
			).toEqual( { sale_price: '22.00' } );
			expect(
				getBulkNumericChangesForProduct( product, {
					sale_price: {
						operation: 'decrease_percent',
						value: '25',
					},
				} )
			).toEqual( { sale_price: '15.00' } );
		} );

		it( 'sets, increases, and decreases stock quantity as integers', () => {
			const product = buildProduct( { stock_quantity: 10 } );

			expect(
				getBulkNumericChangesForProduct( product, {
					stock_quantity: { operation: 'set', value: '7' },
				} )
			).toEqual( { stock_quantity: 7 } );
			expect(
				getBulkNumericChangesForProduct( product, {
					stock_quantity: { operation: 'increase', value: '3' },
				} )
			).toEqual( { stock_quantity: 13 } );
			expect(
				getBulkNumericChangesForProduct( product, {
					stock_quantity: { operation: 'decrease', value: '20' },
				} )
			).toEqual( { stock_quantity: 0 } );
		} );

		it( 'updates the nested cost of goods value', () => {
			const product = buildProduct( {
				cost_of_goods_sold: buildCostOfGoodsSold( '5' ),
			} );

			expect(
				getBulkNumericChangesForProduct( product, {
					cost_of_goods_sold: {
						operation: 'increase',
						value: '2',
					},
				} )
			).toEqual( {
				cost_of_goods_sold: {
					values: [
						{
							defined_value: '7.00',
							effective_value: '5',
						},
					],
					total_value: '5',
				},
			} );
		} );

		it( 'validates projected sale prices before save', () => {
			expect(
				validateBulkNumericEdits(
					[
						buildProduct( {
							regular_price: '10',
							sale_price: '9',
						} ),
					],
					{
						regular_price: {
							operation: 'decrease',
							value: '2',
						},
					}
				)
			).toBe( 'Sale price must be lower than the regular price.' );
		} );
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
			'cost_of_goods_sold',
		];
		const basePriceFieldIds = [ 'regular_price', 'sale_price' ];
		const managedStockFieldIds = [ 'manage_stock', 'stock_quantity' ];
		const stockStatusFieldIds = [ 'manage_stock', 'stock' ];
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
					cost_of_goods_sold: buildCostOfGoodsSold(),
				} ),
			] );

			expect( fieldIds ).toEqual( [
				'name',
				'product_status',
				'catalog_visibility',
				'regular_price',
				'sale_price',
				'schedule_sale',
				'cost_of_goods_sold',
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
				'price',
				'downloadable',
				'external_url',
				'button_text',
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
					cost_of_goods_sold: buildCostOfGoodsSold(),
				} ),
			] );

			expectFieldOrder( fieldIds, [
				'regular_price',
				'sale_price',
				'schedule_sale',
				'cost_of_goods_sold',
			] );
		} );

		it( 'hides cost of goods when the API data is unavailable', () => {
			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'simple',
				} ),
			] );

			expectFieldsHidden( fieldIds, [ 'cost_of_goods_sold' ] );
		} );

		it( 'hides cost of goods when the feature is disabled', () => {
			mockCostOfGoodsSoldFeatureEnabled( false );

			const fieldIds = getVisibleFieldIds( [
				buildProduct( {
					type: 'simple',
					cost_of_goods_sold: buildCostOfGoodsSold(),
				} ),
			] );

			expectFieldsHidden( fieldIds, [ 'cost_of_goods_sold' ] );
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
					'shipping_class',
				] )
			);
			expectFieldOrder( fieldIds, [ 'images', 'downloadable', 'sku' ] );
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
				'grouped_products',
				'images',
				'sku',
				'categories',
				'brands',
				'tags',
				'featured',
			] );
			expectFieldsHidden( fieldIds, [
				'price',
				'regular_price',
				'sale_price',
				'schedule_sale',
				'date_on_sale_from',
				'date_on_sale_to',
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
				'schedule_sale',
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
				'date_on_sale_from',
				'date_on_sale_to',
				'cross_sell_ids',
				'downloadable',
				'upsell_ids',
				'weight',
				'length',
				'width',
				'height',
				'shipping_class',
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
					'shipping_class',
					'weight',
					'length',
					'width',
					'height',
				] )
			);
			expectFieldsHidden( fieldIds, [
				'upsell_ids',
				'cross_sell_ids',
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
				'date_on_sale_from',
				'date_on_sale_to',
			] );
			expect( fieldIds ).toContain( 'schedule_sale' );
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
					cost_of_goods_sold: buildCostOfGoodsSold(),
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'variation_active',
					'regular_price',
					'sale_price',
					'schedule_sale',
					'cost_of_goods_sold',
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
					cost_of_goods_sold: buildCostOfGoodsSold(),
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'images',
					'sku',
					'regular_price',
					'sale_price',
					'schedule_sale',
					'cost_of_goods_sold',
					'stock',
					'manage_stock',
					'variation_active',
					'shipping_class',
					'weight',
					'length',
					'width',
					'height',
				] )
			);
			expectFieldsHidden( fieldIds, parentOwnedFieldIds );
			expectFieldsHidden( fieldIds, [ 'price', 'tax_status' ] );
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

			expect( fieldIds ).toContain( 'downloadable' );
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
					cost_of_goods_sold: buildCostOfGoodsSold(),
				} ),
				buildProduct( {
					id: 34,
					parent_id: 12,
					type: 'variation',
					manage_stock: true,
					on_sale: true,
					sale_price: '12',
					date_on_sale_from: '2026-05-06T00:00:00',
					cost_of_goods_sold: buildCostOfGoodsSold(),
				} ),
			] );

			expect( fieldIds ).toEqual(
				expect.arrayContaining( [
					'regular_price',
					'sale_price',
					'schedule_sale',
					'cost_of_goods_sold',
					...bulkSellableInstanceFieldIds,
				] )
			);
			expectFieldsHidden( fieldIds, [
				...parentOwnedFieldIds,
				'downloadable',
				'sku',
				'price',
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

		it( 'shows downloadable fields for simple product and variation selections', () => {
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

			expect( fieldIds ).toContain( 'downloadable' );
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
				'product_status',
				'variation_active',
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
				'product_status',
				'variation_active',
				'downloadable',
				'sku',
				'stock',
				'stock_quantity',
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
		const getFormFields = ( products: ProductEntityRecord[] ) =>
			getProductTypeFormFields(
				products,
				getVisibleProductEditFields(
					getProductEditFields( productFields ),
					products
				)
			);

		it( 'uses grouped simple product form config', () => {
			const product = buildProduct( {
				type: 'simple',
				virtual: false,
				downloadable: true,
				manage_stock: true,
				cost_of_goods_sold: buildCostOfGoodsSold(),
			} );

			expect( getFormFields( [ product ] ) ).toEqual( [
				{
					id: 'general-fields',
					label: 'General',
					children: [
						'name',
						'product_status',
						'catalog_visibility',
					],
				},
				{
					id: 'price-fields',
					label: 'Price',
					children: [
						'regular_price',
						'sale_price',
						'schedule_sale',
						'cost_of_goods_sold',
					],
				},
				{
					id: 'image-fields',
					label: 'Images',
					children: [ 'images' ],
				},
				{
					id: 'downloadable-files-fields',
					label: 'Downloadable files',
					children: [ 'downloadable' ],
				},
				{
					id: 'inventory-fields',
					label: 'Inventory',
					children: [ 'sku', 'manage_stock', 'stock_quantity' ],
				},
				{
					id: 'product-organization-fields',
					label: 'Product organization',
					children: [ 'categories', 'brands', 'tags', 'featured' ],
				},
				{
					id: 'shipping-fields',
					label: 'Shipping',
					children: [
						'shipping_class',
						{
							id: 'dimensions',
							layout: { type: 'row' },
							children: [ 'length', 'width', 'height' ],
						},
						'weight',
					],
				},
			] );
		} );

		it.each( [
			[ 'simple product', 'simple' ],
			[ 'variation product', 'variation' ],
		] as const )(
			'uses schedule sale as a compound price field for %s',
			( _label, productType ) => {
				const product = buildProduct( {
					type: productType,
					date_on_sale_from: '2026-05-06T00:00:00',
					cost_of_goods_sold: buildCostOfGoodsSold(),
				} );

				const priceGroup = getFormFields( [ product ] ).find(
					( formField ) =>
						typeof formField !== 'string' &&
						formField.id === 'price-fields'
				);

				expect( priceGroup ).toEqual( {
					id: 'price-fields',
					label: 'Price',
					children: [
						'regular_price',
						'sale_price',
						'schedule_sale',
						'cost_of_goods_sold',
					],
				} );
			}
		);

		it( 'omits the price section from grouped product form config', () => {
			const product = buildProduct( {
				type: 'grouped',
			} );

			expect( getFormFields( [ product ] ) ).toEqual( [
				{
					id: 'general-fields',
					label: 'General',
					children: [
						'name',
						'product_status',
						'catalog_visibility',
						'grouped_products',
					],
				},
				{
					id: 'image-fields',
					label: 'Images',
					children: [ 'images' ],
				},
				{
					id: 'inventory-fields',
					label: 'Inventory',
					children: [ 'sku' ],
				},
				{
					id: 'product-organization-fields',
					label: 'Product organization',
					children: [ 'categories', 'brands', 'tags', 'featured' ],
				},
			] );
		} );

		it( 'uses grouped external product form config', () => {
			const product = buildProduct( {
				type: 'external',
			} );

			expect( getFormFields( [ product ] ) ).toEqual( [
				{
					id: 'general-fields',
					label: 'General',
					children: [
						'name',
						'product_status',
						'catalog_visibility',
					],
				},
				{
					id: 'price-fields',
					label: 'Price',
					children: [
						'regular_price',
						'sale_price',
						'schedule_sale',
					],
				},
				{
					id: 'image-fields',
					label: 'Images',
					children: [ 'images' ],
				},
				{
					id: 'buy-button-fields',
					label: 'Buy button',
					children: [ 'external_url', 'button_text' ],
				},
				{
					id: 'inventory-fields',
					label: 'Inventory',
					children: [ 'sku' ],
				},
				{
					id: 'product-organization-fields',
					label: 'Product organization',
					children: [ 'categories', 'brands', 'tags', 'featured' ],
				},
			] );
		} );

		it( 'uses grouped variable parent form config', () => {
			const product = buildProduct( {
				type: 'variable',
				virtual: false,
			} );

			expect( getFormFields( [ product ] ) ).toEqual( [
				{
					id: 'general-fields',
					label: 'General',
					children: [
						'name',
						'product_status',
						'catalog_visibility',
					],
				},
				{
					id: 'image-fields',
					label: 'Images',
					children: [ 'images' ],
				},
				{
					id: 'inventory-fields',
					label: 'Inventory',
					children: [ 'sku', 'manage_stock', 'stock' ],
				},
				{
					id: 'product-organization-fields',
					label: 'Product organization',
					children: [ 'categories', 'brands', 'tags', 'featured' ],
				},
				{
					id: 'shipping-fields',
					label: 'Shipping',
					children: [
						'shipping_class',
						{
							id: 'dimensions',
							layout: { type: 'row' },
							children: [ 'length', 'width', 'height' ],
						},
						'weight',
					],
				},
			] );
		} );

		it( 'uses grouped variation product form config', () => {
			const product = buildProduct( {
				id: 34,
				parent_id: 12,
				type: 'variation',
				virtual: false,
				downloadable: true,
				manage_stock: true,
			} );

			expect( getFormFields( [ product ] ) ).toEqual( [
				{
					id: 'general-fields',
					label: 'General',
					children: [ 'variation_active' ],
				},
				{
					id: 'price-fields',
					label: 'Price',
					children: [
						'regular_price',
						'sale_price',
						'schedule_sale',
					],
				},
				{
					id: 'image-fields',
					label: 'Images',
					children: [ 'images' ],
				},
				{
					id: 'downloadable-files-fields',
					label: 'Downloadable files',
					children: [ 'downloadable' ],
				},
				{
					id: 'inventory-fields',
					label: 'Inventory',
					children: [ 'sku', 'manage_stock', 'stock_quantity' ],
				},
				{
					id: 'shipping-fields',
					label: 'Shipping',
					children: [
						'shipping_class',
						{
							id: 'dimensions',
							layout: { type: 'row' },
							children: [ 'length', 'width', 'height' ],
						},
						'weight',
					],
				},
			] );
		} );

		it( 'prunes empty groups when all descendants are hidden', () => {
			const product = buildProduct( {
				type: 'simple',
				virtual: true,
			} );

			expect( getFormFields( [ product ] ) ).toEqual( [
				{
					id: 'general-fields',
					label: 'General',
					children: [
						'name',
						'product_status',
						'catalog_visibility',
					],
				},
				{
					id: 'price-fields',
					label: 'Price',
					children: [
						'regular_price',
						'sale_price',
						'schedule_sale',
					],
				},
				{
					id: 'image-fields',
					label: 'Images',
					children: [ 'images' ],
				},
				{
					id: 'inventory-fields',
					label: 'Inventory',
					children: [ 'sku', 'manage_stock', 'stock' ],
				},
				{
					id: 'product-organization-fields',
					label: 'Product organization',
					children: [ 'categories', 'brands', 'tags', 'featured' ],
				},
			] );
		} );
	} );
} );
