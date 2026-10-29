/**
 * External dependencies
 */
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import { getSetting } from '@woocommerce/settings';
import useProductAttributes from '@woocommerce/base-context/hooks/use-product-attributes';
import type { ProductCategoryResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import ProductCategoryControl from '../../product-category-control';
import ProductBrandControl from '../../product-brand-control';
import ProductTagControl from '../../product-tag-control';
import ProductAttributeTermControl from '../../product-attribute-term-control';
import { getBrands, getCategories, getProductTags } from '../../utils';

type ConsoleMatchers = { toHaveWarned: () => void };

jest.mock( '@woocommerce/base-context/hooks/use-product-attributes', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn(
		( _key: string, defaultValue: unknown ) => defaultValue
	),
	getSettingWithCoercion: jest.fn(
		( _key: string, defaultValue: unknown ) => defaultValue
	),
} ) );

jest.mock( '../../utils', () => ( {
	getBrands: jest.fn(),
	getCategories: jest.fn(),
	getProductTags: jest.fn(),
} ) );

const mockGetSetting = jest.mocked( getSetting );
const mockUseProductAttributes = jest.mocked( useProductAttributes );
const mockGetProductTags = jest.mocked( getProductTags );

const taxonomyItems: ProductCategoryResponseItem[] = [
	{
		count: 2,
		description: '',
		id: 1,
		image: null,
		name: 'First',
		parent: 0,
		permalink: 'https://example.com/first',
		review_count: 7,
		slug: 'first',
	},
	{
		count: 5,
		description: '',
		id: 2,
		image: null,
		name: 'Selected',
		parent: 0,
		permalink: 'https://example.com/selected',
		review_count: 11,
		slug: 'selected',
	},
];

const taxonomyControls = [
	{
		Control: ProductCategoryControl,
		fetchTerms: jest.mocked( getCategories ),
		label: 'categories',
		showReviewCount: true,
		firstCount: '7 reviews',
		selectedCount: '11 reviews',
	},
	{
		Control: ProductBrandControl,
		fetchTerms: jest.mocked( getBrands ),
		label: 'brands',
		showReviewCount: false,
		firstCount: '2 products',
		selectedCount: '5 products',
	},
];

describe( 'product taxonomy adapter integration', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockGetSetting.mockImplementation(
			( _key: string, defaultValue: unknown ) => defaultValue
		);
		mockUseProductAttributes.mockReturnValue( {
			errorLoadingAttributes: null,
			isLoadingAttributes: false,
			productsAttributes: [],
		} );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it.each( taxonomyControls )(
		'renders $label counts and updates single selections',
		async ( {
			Control,
			fetchTerms,
			showReviewCount,
			firstCount,
			selectedCount,
		} ) => {
			fetchTerms.mockResolvedValue( [
				...taxonomyItems,
				{
					...taxonomyItems[ 0 ],
					id: 3,
					name: 'Child',
					parent: 1,
					slug: 'child',
				},
			] );
			const onChange = jest.fn();
			const { rerender } = render(
				<Control
					isSingle
					onChange={ onChange }
					selected={ [ 2 ] }
					showReviewCount={ showReviewCount }
				/>
			);

			const first = await screen.findByRole( 'radio', {
				name: `First, has ${ firstCount }`,
			} );
			const selected = screen.getByRole( 'radio', {
				name: `Selected, has ${ selectedCount }`,
			} );
			expect( screen.getByText( firstCount ) ).toBeInTheDocument();
			expect( first ).not.toBeChecked();
			expect( selected ).toBeChecked();
			expect( screen.queryByText( 'Child' ) ).not.toBeInTheDocument();

			fireEvent.click( first );
			expect( onChange ).toHaveBeenCalledWith( [
				expect.objectContaining( {
					id: 1,
					name: 'First',
					value: 'first',
					details: taxonomyItems[ 0 ],
				} ),
			] );
			expect(
				screen.getByRole( 'radio', {
					name: `First, Child, has ${ firstCount }`,
				} )
			).toBeInTheDocument();

			rerender(
				<Control
					isSingle
					onChange={ onChange }
					selected={ [ 1 ] }
					showReviewCount={ showReviewCount }
				/>
			);
			expect( first ).toBeChecked();
			expect( selected ).not.toBeChecked();
		}
	);

	it.each( taxonomyControls )(
		'shows $label fetch errors instead of the picker',
		async ( { Control, fetchTerms, label } ) => {
			fetchTerms.mockRejectedValue( new Error( 'Unable to load terms' ) );
			render( <Control onChange={ jest.fn() } selected={ [] } /> );

			expect(
				await screen.findByText( 'Unable to load terms' )
			).toBeInTheDocument();
			expect(
				screen.queryByLabelText( `Search for product ${ label }` )
			).not.toBeInTheDocument();
		}
	);

	it( 'renders tag counts and searches locally when tag limits are disabled', async () => {
		const onChange = jest.fn();
		mockGetProductTags.mockResolvedValue( [
			{
				breadcrumbs: [],
				children: [],
				count: 3,
				id: 9,
				name: 'Sale',
				parent: 0,
				value: 'sale',
			},
		] );

		render(
			<ProductTagControl onChange={ onChange } selected={ [ 9 ] } />
		);

		const tag = await screen.findByRole( 'checkbox', {
			name: 'Sale, has 3 products',
		} );

		expect( screen.getByText( '3 products' ) ).toBeInTheDocument();
		fireEvent.click( tag );
		expect( onChange ).toHaveBeenCalledWith( [] );

		fireEvent.change( screen.getByLabelText( 'Search for product tags' ), {
			target: { value: 'sal' },
		} );
		expect( mockGetProductTags ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'keeps the tag operator available for unresolved selections and coalesces server search', async () => {
		jest.useFakeTimers();
		mockGetSetting.mockImplementation( ( key, defaultValue ) =>
			key === 'limitTags' ? true : defaultValue
		);
		mockGetProductTags.mockResolvedValue( [
			{
				breadcrumbs: [],
				children: [],
				count: 3,
				id: 9,
				name: 'Sale',
				parent: 0,
				value: 'sale',
			},
		] );
		const onOperatorChange = jest.fn();
		const { rerender } = render(
			<ProductTagControl
				onChange={ jest.fn() }
				onOperatorChange={ onOperatorChange }
				operator="all"
				selected={ [ 9, 10 ] }
			/>
		);
		// eslint-disable-next-line jest/valid-expect -- The WordPress matcher has no local type declaration.
		( expect( console ) as unknown as ConsoleMatchers ).toHaveWarned();

		const tag = await screen.findByRole( 'checkbox', {
			name: 'Sale, has 3 products',
		} );
		expect( tag ).toBeChecked();
		const operator = screen.getByRole( 'combobox', {
			name: 'Display products matching',
		} );
		expect( operator ).toHaveValue( 'all' );
		fireEvent.change( operator, { target: { value: 'any' } } );
		expect( onOperatorChange ).toHaveBeenCalledTimes( 1 );
		expect( onOperatorChange.mock.calls[ 0 ][ 0 ] ).toBe( 'any' );

		const search = screen.getByLabelText( 'Search for product tags' );
		fireEvent.change( search, { target: { value: 's' } } );
		fireEvent.change( search, { target: { value: 'su' } } );
		fireEvent.change( search, { target: { value: 'sum' } } );
		await act( async () => {
			jest.advanceTimersByTime( 399 );
		} );
		expect( mockGetProductTags ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			jest.advanceTimersByTime( 1 );
		} );
		expect( mockGetProductTags ).toHaveBeenLastCalledWith( {
			search: 'sum',
			selected: [ 9, 10 ],
		} );
		expect( mockGetProductTags ).toHaveBeenCalledTimes( 2 );

		rerender(
			<ProductTagControl
				onChange={ jest.fn() }
				onOperatorChange={ onOperatorChange }
				operator="any"
				selected={ [ 9 ] }
			/>
		);
		expect( operator ).not.toBeVisible();
	} );

	it( 'clears tag loading after a failed request', async () => {
		mockGetProductTags.mockRejectedValue( new Error( 'Request failed' ) );
		const { container } = render(
			<ProductTagControl onChange={ jest.fn() } selected={ [] } />
		);

		expect(
			container.querySelector( '.components-spinner' )
		).toBeInTheDocument();

		await waitFor( () => {
			expect(
				container.querySelector( '.components-spinner' )
			).not.toBeInTheDocument();
		} );
		expect(
			screen.getByText(
				'You have not set up any product tags on your store.'
			)
		).toBeInTheDocument();
	} );

	it( 'renders attribute groups and selects terms even when their IDs collide', () => {
		const onChange = jest.fn();
		mockUseProductAttributes.mockReturnValue( {
			errorLoadingAttributes: null,
			isLoadingAttributes: false,
			productsAttributes: [
				{
					count: 1,
					has_archives: true,
					id: 12,
					label: 'Color',
					name: 'Color',
					orderby: 'name',
					parent: 0,
					taxonomy: 'pa_color',
					terms: [
						{
							attr_slug: 'pa_color',
							count: 3,
							description: '',
							id: 12,
							name: 'Blue',
							parent: 12,
							slug: 'blue',
						},
					],
					type: 'select',
				},
				{
					count: 0,
					has_archives: true,
					id: 8,
					label: 'Size',
					name: 'Size',
					orderby: 'name',
					parent: 0,
					taxonomy: 'pa_size',
					terms: [],
					type: 'select',
				},
			],
		} );

		const { rerender } = render(
			<ProductAttributeTermControl
				isCompact={ false }
				onChange={ onChange }
				operator="any"
				selected={ [] }
			/>
		);

		expect( screen.getByText( '1 term' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', { name: /Size/ } )
		).toBeDisabled();

		fireEvent.click( screen.getByRole( 'treeitem', { name: /Color/ } ) );
		const blue = screen.getByRole( 'checkbox', {
			name: 'Color: Blue, has 3 products',
		} );
		expect( screen.getByText( '3 products' ) ).toBeInTheDocument();
		fireEvent.click( blue );
		expect( onChange ).toHaveBeenCalledWith( [
			expect.objectContaining( {
				id: 12,
				name: 'Blue',
				value: 'pa_color',
			} ),
		] );
		rerender(
			<ProductAttributeTermControl
				isCompact={ false }
				onChange={ onChange }
				operator="any"
				selected={ [ { id: 12 } ] }
			/>
		);
		expect( blue ).toBeChecked();
		fireEvent.click( blue );
		expect( onChange ).toHaveBeenLastCalledWith( [] );
	} );

	it( 'shows attribute fetch errors instead of the picker', () => {
		mockUseProductAttributes.mockReturnValue( {
			errorLoadingAttributes: {
				message: 'Unable to load attributes',
				type: 'general',
			},
			isLoadingAttributes: false,
			productsAttributes: [],
		} );
		render(
			<ProductAttributeTermControl
				isCompact={ false }
				onChange={ jest.fn() }
				operator="any"
				selected={ [] }
			/>
		);
		expect(
			screen.getByText( 'Unable to load attributes' )
		).toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Search for product attributes' )
		).not.toBeInTheDocument();
	} );
} );
