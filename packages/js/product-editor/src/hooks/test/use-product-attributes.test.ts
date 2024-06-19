/**
 * External dependencies
 */
import { renderHook, cleanup } from '@testing-library/react-hooks';
import type {
	ProductProductAttribute,
	ProductAttributeTerm,
} from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useProductAttributes } from '../use-product-attributes';

const attributeTerms: Record< number, ProductAttributeTerm[] > = {
	2: [
		{
			id: 64,
			name: 'Blue',
			slug: 'blue',
			description: '',
			menu_order: 0,
			count: 2,
		},
		{
			id: 76,
			name: 'Green',
			slug: 'green',
			description: '',
			menu_order: 0,
			count: 1,
		},
		{
			id: 63,
			name: 'Red',
			slug: 'red',
			description: '',
			menu_order: 0,
			count: 2,
		},
		{
			id: 65,
			name: 'Velvet',
			slug: 'velvet',
			description: '',
			menu_order: 0,
			count: 2,
		},
		{
			id: 66,
			name: 'Yellow',
			slug: 'yellow',
			description: '',
			menu_order: 0,
			count: 2,
		},
	],
	3: [
		{
			id: 64,
			name: 'Small',
			slug: 'small',
			description: '',
			menu_order: 0,
			count: 2,
		},
		{
			id: 76,
			name: 'Medium',
			slug: 'medium',
			description: '',
			menu_order: 0,
			count: 1,
		},
		{
			id: 63,
			name: 'Large',
			slug: 'large',
			description: '',
			menu_order: 0,
			count: 2,
		},
	],
};

jest.useFakeTimers();
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	resolveSelect: jest.fn().mockReturnValue( {
		getProductAttributeTerms: jest
			.fn()
			.mockImplementation( ( { attribute_id } ) => {
				return new Promise( ( resolve ) => {
					setTimeout( () => {
						if ( attributeTerms[ attribute_id ] ) {
							return resolve( attributeTerms[ attribute_id ] );
						}
						return resolve( [] );
					}, 100 );
				} );
			} ),
	} ),
} ) );

const testAttributes: ProductProductAttribute[] = [
	{
		id: 0,
		name: 'Local',
		options: [ 'option 1', 'option 2' ],
		position: 0,
		variation: false,
		visible: false,
		slug: 'local',
	},
	{
		id: 2,
		name: 'Global: Color',
		options: [ 'Red', 'Yellow' ],
		position: 1,
		variation: false,
		visible: true,
		slug: 'color',
	},
	{
		id: 3,
		name: 'Global: Size',
		options: [ 'Small', 'Medium', 'Large' ],
		position: 2,
		variation: false,
		visible: true,
		slug: 'size',
	},
];

describe( 'useProductAttributes', () => {
	afterEach( () => {
		cleanup();
		( resolveSelect as jest.Mock ).mockClear();
		jest.runOnlyPendingTimers();
	} );

	it( 'should return empty array when no attributes', async () => {
		const { result, waitForNextUpdate } = renderHook(
			useProductAttributes,
			{
				initialProps: {
					allAttributes: [],
					onChange: jest.fn(),
					isVariationAttributes: false,
					productId: 123,
				},
			}
		);
		result.current.fetchAttributes();
		await waitForNextUpdate();
		expect( resolveSelect ).not.toHaveBeenCalled();
		expect( result.current.attributes ).toEqual( [] );
	} );

	describe( 'handleChange', () => {
		it( 'should call onChange when handleChange is called with updated attributes', async () => {
			const allAttributes = [
				{ ...testAttributes[ 1 ] },
				{ ...testAttributes[ 2 ] },
			];
			const onChange = jest.fn();
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: false,
						productId: 123,
					},
				}
			);
			result.current.fetchAttributes();
			jest.runOnlyPendingTimers();
			await waitForNextUpdate();
			result.current.handleChange( [
				{ ...allAttributes[ 0 ], isDefault: false },
				{ ...allAttributes[ 1 ], isDefault: false },
				{ ...testAttributes[ 0 ], isDefault: false },
			] );
			expect( onChange ).toHaveBeenCalledWith(
				[
					{ ...allAttributes[ 0 ], position: 0 },
					{ ...allAttributes[ 1 ], position: 1 },
					{ ...testAttributes[ 0 ], variation: false, position: 2 },
				],
				[]
			);
		} );

		it( 'should keep both variable and non variable as part of the onChange list, when isVariation is false', async () => {
			const allAttributes = [
				{ ...testAttributes[ 1 ], variation: true },
				{ ...testAttributes[ 2 ], variation: true },
			];
			const onChange = jest.fn();
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: false,
						productId: 123,
					},
				}
			);
			jest.runOnlyPendingTimers();
			result.current.fetchAttributes();
			await waitForNextUpdate();
			result.current.handleChange( [
				{ ...testAttributes[ 0 ], isDefault: false },
			] );
			expect( onChange ).toHaveBeenCalledWith(
				[
					{ ...testAttributes[ 0 ], variation: false, position: 0 },
					{ ...allAttributes[ 0 ], position: 1 },
					{ ...allAttributes[ 1 ], position: 2 },
				],
				[]
			);
		} );

		it( 'should keep both variable and non variable as part of the onChange list, when isVariation is true', async () => {
			const allAttributes = [
				{ ...testAttributes[ 1 ] },
				{ ...testAttributes[ 2 ] },
			];
			const onChange = jest.fn();
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: true,
						productId: 123,
					},
				}
			);
			jest.runOnlyPendingTimers();
			result.current.fetchAttributes();
			await waitForNextUpdate();
			result.current.handleChange( [
				{ ...testAttributes[ 0 ], isDefault: false },
			] );
			expect( onChange ).toHaveBeenCalledWith(
				[
					{ ...allAttributes[ 0 ], position: 0 },
					{ ...allAttributes[ 1 ], position: 1 },
					{ ...testAttributes[ 0 ], variation: true, position: 2 },
				],
				[]
			);
		} );

		it( 'should remove duplicate globals', async () => {
			const allAttributes = [
				{ ...testAttributes[ 1 ] },
				{ ...testAttributes[ 2 ] },
			];
			const onChange = jest.fn();
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: true,
						productId: 123,
					},
				}
			);
			jest.runOnlyPendingTimers();
			result.current.fetchAttributes();
			await waitForNextUpdate();
			result.current.handleChange( [
				{ ...testAttributes[ 1 ], isDefault: false },
			] );
			expect( onChange ).toHaveBeenCalledWith(
				[
					{ ...allAttributes[ 1 ], position: 0 },
					{ ...allAttributes[ 0 ], position: 1, variation: true },
				],
				[]
			);
		} );

		it( 'should remove duplicate locals by name', async () => {
			const allAttributes = [
				{ ...testAttributes[ 0 ] },
				{ ...testAttributes[ 1 ] },
			];
			const onChange = jest.fn();
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: true,
						productId: 123,
					},
				}
			);
			jest.runOnlyPendingTimers();
			result.current.fetchAttributes();
			await waitForNextUpdate();
			result.current.handleChange( [
				{ ...testAttributes[ 0 ], isDefault: false },
			] );
			expect( onChange ).toHaveBeenCalledWith(
				[
					{ ...allAttributes[ 1 ], position: 0 },
					{ ...allAttributes[ 0 ], position: 1, variation: true },
				],
				[]
			);
		} );

		it( 'should pass default attributes as second param, defaulting to true when isDefault is not defined', async () => {
			const allAttributes = [
				{ ...testAttributes[ 0 ] },
				{ ...testAttributes[ 1 ] },
			];
			const onChange = jest.fn();
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: true,
						productId: 123,
					},
				}
			);
			jest.runOnlyPendingTimers();
			result.current.fetchAttributes();
			await waitForNextUpdate();
			result.current.handleChange( [ { ...testAttributes[ 0 ] } ] );
			expect( onChange ).toHaveBeenCalledWith(
				[
					{ ...allAttributes[ 1 ], position: 0 },
					{ ...allAttributes[ 0 ], position: 1, variation: true },
				],
				[
					{
						id: testAttributes[ 0 ].id,
						name: testAttributes[ 0 ].name,
						option: testAttributes[ 0 ].options[ 0 ],
					},
				]
			);
		} );

		it( 'should pass default attributes as second param, when isDefault is true', async () => {
			const allAttributes = [
				{ ...testAttributes[ 0 ] },
				{ ...testAttributes[ 1 ] },
			];
			const onChange = jest.fn();
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: true,
						productId: 123,
					},
				}
			);
			jest.runOnlyPendingTimers();
			result.current.fetchAttributes();
			await waitForNextUpdate();
			result.current.handleChange( [
				{ ...testAttributes[ 0 ], isDefault: true },
				{ ...testAttributes[ 1 ], isDefault: true },
			] );
			expect( onChange ).toHaveBeenCalledWith(
				[
					{ ...allAttributes[ 0 ], position: 0, variation: true },
					{ ...allAttributes[ 1 ], position: 1, variation: true },
				],
				[
					{
						id: testAttributes[ 0 ].id,
						name: testAttributes[ 0 ].name,
						option: testAttributes[ 0 ].options[ 0 ],
					},
					{
						id: testAttributes[ 1 ].id,
						name: testAttributes[ 1 ].name,
						option: testAttributes[ 1 ].options[ 0 ],
					},
				]
			);
		} );
	} );

	describe( 'is not variation', () => {
		it( 'should filter out variation attributes', async () => {
			const allAttributes = [
				{ ...testAttributes[ 0 ] },
				{ ...testAttributes[ 1 ], variation: true },
				{ ...testAttributes[ 2 ] },
			];
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange: jest.fn(),
						isVariationAttributes: false,
						productId: 123,
					},
				}
			);
			result.current.fetchAttributes();
			jest.runOnlyPendingTimers();
			await waitForNextUpdate();
			expect( result.current.attributes.length ).toBe( 2 );
			// Sets global attributes first.
			expect( result.current.attributes[ 0 ].name ).toEqual(
				allAttributes[ 2 ].name
			);
			expect( result.current.attributes[ 1 ].name ).toEqual(
				allAttributes[ 0 ].name
			);
		} );

		it( 'should update array if allAttributes update', async () => {
			const allAttributes = [
				{ ...testAttributes[ 0 ] },
				{ ...testAttributes[ 1 ], variation: true },
				{ ...testAttributes[ 2 ] },
			];
			const onChange = jest.fn();
			const { result, rerender, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: false,
						productId: 123,
					},
				}
			);
			result.current.fetchAttributes();
			jest.runOnlyPendingTimers();
			await waitForNextUpdate();
			expect( result.current.attributes.length ).toBe( 2 );

			const filteredAttributes = [
				allAttributes[ 0 ],
				allAttributes[ 1 ],
			];

			rerender( {
				allAttributes: filteredAttributes,
				onChange,
				isVariationAttributes: false,
				productId: 123,
			} );
			result.current.fetchAttributes();
			jest.runOnlyPendingTimers();
			await waitForNextUpdate();
			expect( result.current.attributes.length ).toBe( 1 );
			expect( result.current.attributes[ 0 ].name ).toEqual(
				allAttributes[ 0 ].name
			);
		} );

		it( 'sets terms for any global attributes and leaves options the same', async () => {
			const allAttributes = [
				{ ...testAttributes[ 0 ] },
				{ ...testAttributes[ 1 ] },
				{ ...testAttributes[ 2 ] },
			];
			const onChange = jest.fn();
			const { result, waitForNextUpdate } = renderHook(
				useProductAttributes,
				{
					initialProps: {
						allAttributes,
						onChange,
						isVariationAttributes: false,
						productId: 123,
					},
				}
			);
			result.current.fetchAttributes();
			jest.runOnlyPendingTimers();
			await waitForNextUpdate();
			expect( result.current.attributes.length ).toBe( 3 );
			expect( result.current.attributes[ 0 ].terms ).toEqual(
				attributeTerms[ result.current.attributes[ 0 ].id ].filter(
					( t ) => allAttributes[ 1 ].options.includes( t.name )
				)
			);
			expect( result.current.attributes[ 0 ].options ).toEqual(
				result.current.attributes[ 0 ].options
			);
			expect( result.current.attributes[ 1 ].terms ).toEqual(
				attributeTerms[ result.current.attributes[ 1 ].id ]
			);
		} );
	} );
} );
