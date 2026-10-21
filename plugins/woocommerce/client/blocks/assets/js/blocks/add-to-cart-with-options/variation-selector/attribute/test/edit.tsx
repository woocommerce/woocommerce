/**
 * External dependencies
 */
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import * as hooks from '@woocommerce/base-context/hooks';
import type { ComponentProps, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import AttributeItemTemplateEdit from '../edit';
import { DEFAULT_ATTRIBUTES } from '../constants';

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/base-context/hooks' ),
} ) );

// A selected row renders the inner blocks; an unselected one renders a
// clickable preview. Tagging each row, and both of those, lets the tests tell
// them apart, since the component renders no attribute-identifying markup of
// its own.
jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	BlockContextProvider: ( { children }: { children: ReactNode } ) => (
		<div data-testid="attribute-row">{ children }</div>
	),
	InspectorControls: () => null,
	useBlockProps: jest.fn( () => ( {} ) ),
	useInnerBlocksProps: jest.fn( () => ( {
		'data-testid': 'attribute-inner-blocks',
	} ) ),
	__experimentalUseBlockPreview: jest.fn( () => ( {
		'data-testid': 'attribute-preview',
	} ) ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	// Runs the selector against a stub store that only answers `getBlocks`. Any
	// other `useSelect` consumer pulled into this tree then fails loudly instead
	// of silently receiving this component's return value.
	useSelect: jest.fn(
		( mapSelect: ( select: ( store: unknown ) => unknown ) => unknown ) =>
			mapSelect( () => ( { getBlocks: () => [] } ) )
	),
} ) );

jest.mock( '@woocommerce/shared-context', () => ( {
	...jest.requireActual( '@woocommerce/shared-context' ),
	useProductDataContext: jest.fn(),
} ) );

const sharedContext = jest.requireMock( '@woocommerce/shared-context' );

type EditProps = ComponentProps< typeof AttributeItemTemplateEdit >;

// Checked against the props it actually supplies, so a typo inside
// `attributes` still fails to compile. The widening cast below is only there
// because the fixture deliberately omits `isSelected`, `context` and
// `className`.
const editProps = {
	attributes: {
		displayStyle: 'woocommerce/product-filter-chips',
		autoselect: false,
		disabledAttributesAction: 'disable',
	},
	setAttributes: jest.fn(),
	clientId: 'test-client-id',
} satisfies Pick< EditProps, 'attributes' | 'setAttributes' | 'clientId' >;

const renderEdit = () =>
	render(
		<AttributeItemTemplateEdit
			{ ...( editProps as unknown as EditProps ) }
		/>
	);

/**
 * Builds an attribute in the shape the Store API returns. Non-taxonomy
 * ("custom") attributes always carry id 0, and so do their terms.
 *
 * @param name     Attribute label.
 * @param taxonomy Taxonomy name, or null for a custom attribute.
 * @param id       Attribute ID; 0 for custom attributes.
 */
const attribute = ( name: string, taxonomy: string | null, id: number ) => ( {
	id,
	taxonomy,
	name,
	has_variations: true,
	terms: [
		{ id: taxonomy ? id * 10 + 1 : 0, slug: 'one', name: 'One' },
		{ id: taxonomy ? id * 10 + 2 : 0, slug: 'two', name: 'Two' },
	],
} );

const renderWithAttributes = (
	attributes: ReturnType< typeof attribute >[]
) => {
	sharedContext.useProductDataContext.mockReturnValue( {
		product: { id: 15, name: 'Hoodie', type: 'variable', attributes },
	} );

	return renderEdit();
};

/**
 * Reports which row is currently editable, by position.
 *
 * Exactly one row renders inner blocks; every other row renders a preview. The
 * returned index is the position of the editable one.
 */
const selectedRowIndex = () =>
	screen
		.getAllByTestId( 'attribute-row' )
		.findIndex(
			( row ) =>
				!! within( row ).queryByTestId( 'attribute-inner-blocks' )
		);

describe( 'Variation Selector attribute template edit', () => {
	let useCollectionSpy: jest.SpyInstance;

	beforeEach( () => {
		useCollectionSpy = jest
			.spyOn( hooks, 'useCollection' )
			.mockReturnValue( { results: [], isLoading: false } );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'does not query the Store API while showing placeholder attributes', () => {
		sharedContext.useProductDataContext.mockReturnValue( { product: {} } );

		renderEdit();

		const calls = useCollectionSpy.mock.calls.map( ( [ args ] ) => args );

		calls.forEach( ( args ) => {
			expect( args.shouldSelect ).toBe( false );
		} );
		// Every placeholder attribute reached the hook, so the assertion above
		// cannot pass by never running.
		expect(
			new Set( calls.map( ( args ) => args.resourceValues[ 0 ] ) )
		).toEqual( new Set( DEFAULT_ATTRIBUTES.map( ( attr ) => attr.id ) ) );
	} );

	it( 'queries the Store API for a real variable product attribute', () => {
		sharedContext.useProductDataContext.mockReturnValue( {
			product: {
				id: 15,
				name: 'Hoodie',
				type: 'variable',
				attributes: [
					{
						id: 4,
						taxonomy: 'pa_color',
						name: 'Color',
						has_variations: true,
						terms: [
							{ id: 27, slug: 'blue', name: 'Blue' },
							{ id: 28, slug: 'red', name: 'Red' },
						],
					},
				],
			},
		} );

		renderEdit();

		expect( useCollectionSpy ).toHaveBeenCalled();
		useCollectionSpy.mock.calls.forEach( ( [ args ] ) => {
			expect( args.shouldSelect ).toBe( true );
			expect( args.resourceValues ).toEqual( [ 4 ] );
			expect( args.query.include ).toEqual( [ 27, 28 ] );
		} );
	} );

	it( 'selects a custom attribute row when it is clicked', async () => {
		const user = userEvent.setup();
		// A global attribute followed by a custom one, which the Store API
		// reports as id 0 - the payload from #68197. The global id is 1 on
		// purpose: it collides with the custom row's index, so this case also
		// fails if the identity ever becomes `attribute.id || index`.
		renderWithAttributes( [
			attribute( 'Color', 'pa_color', 1 ),
			attribute( 'Fit', null, 0 ),
		] );

		expect( selectedRowIndex() ).toBe( 0 );

		await user.click( screen.getByTestId( 'attribute-preview' ) );

		expect( selectedRowIndex() ).toBe( 1 );
	} );

	it( 'keeps exactly one row editable when every attribute is custom', async () => {
		const user = userEvent.setup();
		renderWithAttributes( [
			attribute( 'Size', null, 0 ),
			attribute( 'Fit', null, 0 ),
		] );

		expect(
			screen.getAllByTestId( 'attribute-inner-blocks' )
		).toHaveLength( 1 );
		expect( selectedRowIndex() ).toBe( 0 );

		await user.click( screen.getByTestId( 'attribute-preview' ) );

		expect(
			screen.getAllByTestId( 'attribute-inner-blocks' )
		).toHaveLength( 1 );
		expect( selectedRowIndex() ).toBe( 1 );
	} );

	it( 'does not render duplicate React keys for custom attributes', () => {
		const errorSpy = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => undefined );

		renderWithAttributes( [
			attribute( 'Size', null, 0 ),
			attribute( 'Fit', null, 0 ),
		] );

		const duplicateKeyWarnings = errorSpy.mock.calls.filter( ( call ) =>
			String( call[ 0 ] ).includes( 'same key' )
		);

		expect( duplicateKeyWarnings ).toHaveLength( 0 );

		errorSpy.mockRestore();
	} );
} );
