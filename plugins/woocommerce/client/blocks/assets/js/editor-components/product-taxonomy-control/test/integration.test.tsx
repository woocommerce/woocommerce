/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { getSetting } from '@woocommerce/settings';
import useProductAttributes from '@woocommerce/base-context/hooks/use-product-attributes';

/**
 * Internal dependencies
 */
import ProductTagControl from '../../product-tag-control';
import ProductAttributeTermControl from '../../product-attribute-term-control';
import { getProductTags } from '../../utils';

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	withInstanceId: < T, >( component: T ): T => component,
} ) );

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
	getProductTags: jest.fn(),
} ) );

const mockGetSetting = getSetting as unknown as jest.Mock;
const mockUseProductAttributes = useProductAttributes as jest.MockedFunction<
	typeof useProductAttributes
>;
const mockGetProductTags = getProductTags as unknown as jest.Mock;

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

	it( 'renders attribute parents, terms, and disabled empty attributes', () => {
		mockUseProductAttributes.mockReturnValue( {
			errorLoadingAttributes: null,
			isLoadingAttributes: false,
			productsAttributes: [
				{
					count: 1,
					has_archives: true,
					id: 7,
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
							parent: 7,
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

		render(
			<ProductAttributeTermControl
				isCompact={ false }
				onChange={ jest.fn() }
				operator="any"
				selected={ [] }
			/>
		);

		expect( screen.getByText( '1 term' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', { name: /Size/ } )
		).toBeDisabled();

		const colorRow = screen
			.getByText( 'Color' )
			.closest( '[role="treeitem"]' );

		if ( ! colorRow ) {
			throw new Error( 'The Color attribute row was not rendered.' );
		}

		fireEvent.click( colorRow );
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Color: Blue, has 3 products',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( '3 products' ) ).toBeInTheDocument();
	} );
} );
