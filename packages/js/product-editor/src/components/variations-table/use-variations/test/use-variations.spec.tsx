/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { resolveSelect, useSelect } from '@wordpress/data';
import type { ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { useVariations } from '../use-variations';

// Mock WordPress dependencies
jest.mock( '@wordpress/core-data', () => ( {
	createSelector: jest.fn(),
	useEntityProp: jest.fn().mockReturnValue( [ 123 ] ),
	useEntityRecord: jest.fn().mockReturnValue( {
		editedRecord: {
			id: 123,
			type: 'variable',
			variations: [],
		},
		hasEdits: false,
		isLoading: false,
	} ),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	experimentalProductVariationsStore: 'wc/experimental/product-variations',
} ) );

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn( () => ( {
		invalidateResolution: jest.fn(),
	} ) ),
	resolveSelect: jest.fn( () => ( {
		getProductVariations: jest.fn(),
		getProductVariationsTotalCount: jest.fn(),
	} ) ),
	useSelect: jest.fn( () => {
		return {
			isGeneratingVariations: false,
			generateError: null,
			getProductVariations: jest.fn(),
			getProductVariationsTotalCount: jest.fn(),
		};
	} ),
} ) );

describe( 'useVariations', () => {
	const mockProductId = 123;
	const mockVariation: ProductVariation = {
		id: 1,
		attributes: [],
		downloads: [],
		name: '',
		parent_id: mockProductId,
		menu_order: 0,
		status: 'publish',
		description: '',
		sku: '',
		regular_price: '',
		sale_price: '',
		date_created: '',
		date_created_gmt: '',
		date_modified: '',
		date_modified_gmt: '',
		tax_status: 'taxable',
		tax_class: '',
		manage_stock: false,
		stock_quantity: null,
		stock_status: 'instock',
		backorders: 'no',
		weight: '',
		dimensions: {
			length: '',
			width: '',
			height: '',
		},
		shipping_class: '',
		shipping_class_id: 0,
		image: undefined,
		permalink: '',
	};

	beforeEach( () => {
		jest.clearAllMocks();
		( resolveSelect as jest.Mock ).mockReturnValue( {
			getProductVariations: jest
				.fn()
				.mockResolvedValue( [ mockVariation ] ),
			getProductVariationsTotalCount: jest.fn().mockResolvedValue( 1 ),
		} );
		( useSelect as jest.Mock ).mockImplementation( () => ( {
			isGeneratingVariations: false,
			generateError: null,
			getProductVariations: jest
				.fn()
				.mockResolvedValue( [ mockVariation ] ),
			getProductVariationsTotalCount: jest.fn().mockResolvedValue( 1 ),
		} ) );
	} );

	it( 'should fetch variations with default orderby parameter', async () => {
		const { result } = renderHook( () =>
			useVariations( { productId: mockProductId } )
		);

		await act( async () => {
			result.current.getCurrentVariations();
		} );

		const mockSelect = resolveSelect as jest.Mock;
		const getProductVariations = mockSelect().getProductVariations;

		expect( getProductVariations ).toHaveBeenCalledWith(
			expect.objectContaining( {
				orderby: 'menu_order',
				product_id: mockProductId,
			} )
		);
	} );

	it( 'should handle pagination correctly', async () => {
		const { result } = renderHook( () =>
			useVariations( { productId: mockProductId } )
		);

		await act( async () => {
			result.current.onPageChange( 2 );
		} );

		const mockSelect = resolveSelect as jest.Mock;
		const getProductVariations = mockSelect().getProductVariations;

		expect( getProductVariations ).toHaveBeenCalledWith(
			expect.objectContaining( {
				page: 2,
				product_id: mockProductId,
			} )
		);
	} );

	it( 'should set loading state correctly', async () => {
		const { result } = renderHook( () =>
			useVariations( { productId: mockProductId } )
		);

		expect( result.current.isLoading ).toBe( false );

		await act( async () => {
			result.current.getCurrentVariations();
		} );

		expect( result.current.isLoading ).toBe( false );
	} );

	it( 'should handle errors correctly', async () => {
		const error = new Error( 'Test error' );
		( resolveSelect as jest.Mock ).mockReturnValue( {
			getProductVariations: jest.fn().mockRejectedValue( error ),
			getProductVariationsTotalCount: jest
				.fn()
				.mockRejectedValue( error ),
		} );

		const { result } = renderHook( () =>
			useVariations( { productId: mockProductId } )
		);

		await act( async () => {
			result.current.getCurrentVariations();
		} );

		expect( result.current.variationsError ).toBe( error );
	} );
} );
