/**
 * External dependencies
 */
import { render } from '@testing-library/react';
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

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	BlockContextProvider: ( { children }: { children: ReactNode } ) => (
		<div>{ children }</div>
	),
	InspectorControls: () => null,
	useBlockProps: jest.fn( () => ( {} ) ),
	useInnerBlocksProps: jest.fn( () => ( {} ) ),
	__experimentalUseBlockPreview: jest.fn( () => ( {} ) ),
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
		).toEqual(
			new Set( DEFAULT_ATTRIBUTES.map( ( attribute ) => attribute.id ) )
		);
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
} );
