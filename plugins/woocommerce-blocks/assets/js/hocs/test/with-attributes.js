/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import withAttributes from '../with-attributes';
import * as mockUtils from '../../components/utils';
import * as mockBaseUtils from '../../base/utils/errors';

jest.mock( '../../components/utils', () => ( {
	getAttributes: jest.fn(),
	getTerms: jest.fn(),
} ) );

jest.mock( '../../base/utils/errors', () => ( {
	formatError: jest.fn(),
} ) );

jest.mock( 'lodash', () => ( {
	...jest.requireActual( 'lodash' ),
	debounce: ( func ) => func,
} ) );

const mockAttributes = [
	{ id: 1, name: 'Color', slug: 'color' },
	{ id: 2, name: 'Size', slug: 'size' },
];
const mockAttributesWithParent = [
	{ id: 1, name: 'Color', slug: 'color', parent: 0 },
	{ id: 2, name: 'Size', slug: 'size', parent: 0 },
];
const selected = [ { id: 11, attr_slug: 'color' } ];
const TestComponent = withAttributes( ( props ) => {
	return (
		<div
			attributes={ props.attributes }
			error={ props.error }
			expandedAttribute={ props.expandedAttribute }
			onExpandAttribute={ props.onExpandAttribute }
			isLoading={ props.isLoading }
			termsAreLoading={ props.termsAreLoading }
			termsList={ props.termsList }
		/>
	);
} );

describe( 'withAttributes Component', () => {
	afterEach( () => {
		mockUtils.getAttributes.mockReset();
		mockUtils.getTerms.mockReset();
		mockBaseUtils.formatError.mockReset();
	} );

	describe( 'lifecycle events', () => {
		let getAttributesPromise;

		beforeEach( () => {
			getAttributesPromise = Promise.resolve( mockAttributes );
			mockUtils.getAttributes.mockReturnValue( getAttributesPromise );
			mockUtils.getTerms.mockResolvedValue( [] );
		} );

		it( 'getAttributes is called on mount', () => {
			TestRenderer.create( <TestComponent /> );
			const { getAttributes } = mockUtils;

			expect( getAttributes ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getTerms is called on component update', () => {
			const renderer = TestRenderer.create( <TestComponent /> );
			let props = renderer.root.findByType( 'div' ).props;

			props.onExpandAttribute( 1 );

			const { getTerms } = mockUtils;
			props = renderer.root.findByType( 'div' ).props;

			expect( getTerms ).toHaveBeenCalledWith( 1 );
			expect( getTerms ).toHaveBeenCalledTimes( 1 );
			expect( props.expandedAttribute ).toBe( 1 );
		} );

		it( 'getTerms is called on mount if there was an attribute selected', async () => {
			const renderer = TestRenderer.create(
				<TestComponent selected={ selected } />
			);

			await getAttributesPromise;

			const { getTerms } = mockUtils;
			const props = renderer.root.findByType( 'div' ).props;

			expect( getTerms ).toHaveBeenCalledWith( 1 );
			expect( getTerms ).toHaveBeenCalledTimes( 1 );
			expect( props.expandedAttribute ).toBe( 1 );
		} );
	} );

	describe( 'when the API returns attributes data', () => {
		let renderer;

		beforeEach( () => {
			mockUtils.getAttributes.mockResolvedValue( mockAttributes );
			renderer = TestRenderer.create( <TestComponent /> );
		} );

		it( 'sets the attributes props', () => {
			const props = renderer.root.findByType( 'div' ).props;

			expect( props.error ).toBeNull();
			expect( props.isLoading ).toBe( false );
			expect( props.attributes ).toEqual( mockAttributesWithParent );
		} );
	} );

	describe( 'when the API returns an error', () => {
		const error = { message: 'There was an error.' };
		const getAttributesPromise = Promise.reject( error );
		const formattedError = { message: 'There was an error.', type: 'api' };
		let renderer;

		beforeEach( () => {
			mockUtils.getAttributes.mockReturnValue( getAttributesPromise );
			mockBaseUtils.formatError.mockReturnValue( formattedError );
			renderer = TestRenderer.create( <TestComponent /> );
		} );

		test( 'sets the error prop', async () => {
			await expect( () => getAttributesPromise() ).toThrow();

			const { formatError } = mockBaseUtils;
			const props = renderer.root.findByType( 'div' ).props;

			expect( formatError ).toHaveBeenCalledWith( error );
			expect( formatError ).toHaveBeenCalledTimes( 1 );
			expect( props.error ).toEqual( formattedError );
			expect( props.isLoading ).toBe( false );
			expect( props.attributes ).toEqual( [] );
		} );
	} );
} );
