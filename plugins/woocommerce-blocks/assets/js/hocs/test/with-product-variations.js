/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import withProductVariations from '../with-product-variations';
import * as mockUtils from '../../components/utils';
import * as mockBaseUtils from '../../base/utils/errors';

jest.mock( '../../components/utils', () => ( {
	getProductVariations: jest.fn(),
} ) );

jest.mock( '../../base/utils/errors', () => ( {
	formatError: jest.fn(),
} ) );

const mockProducts = [
	{ id: 1, name: 'Hoodie', variations: [ 3, 4 ] },
	{ id: 2, name: 'Backpack' },
];
const mockVariations = [ { id: 3, name: 'Blue' }, { id: 4, name: 'Red' } ];
const TestComponent = withProductVariations( ( props ) => {
	return (
		<div
			error={ props.error }
			expandedProduct={ props.expandedProduct }
			isLoading={ props.isLoading }
			variations={ props.variations }
			variationsLoading={ props.variationsLoading }
		/>
	);
} );
const render = () => {
	return TestRenderer.create(
		<TestComponent
			error={ null }
			isLoading={ false }
			products={ mockProducts }
			selected={ [ 1 ] }
			showVariations={ true }
		/>
	);
};

describe( 'withProductVariations Component', () => {
	let renderer;
	afterEach( () => {
		mockUtils.getProductVariations.mockReset();
	} );

	describe( 'lifecycle events', () => {
		beforeEach( () => {
			mockUtils.getProductVariations.mockImplementation( () =>
				Promise.resolve( mockVariations )
			);
		} );

		it( 'getProductVariations is called on mount', () => {
			renderer = render();
			const { getProductVariations } = mockUtils;

			expect( getProductVariations ).toHaveBeenCalledWith( 1 );
			expect( getProductVariations ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getProductVariations is called on component update', () => {
			renderer = TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
				/>
			);
			const { getProductVariations } = mockUtils;

			expect( getProductVariations ).toHaveBeenCalledTimes( 0 );

			renderer.update(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
					selected={ [ 1 ] }
					showVariations={ true }
				/>
			);

			expect( getProductVariations ).toHaveBeenCalledWith( 1 );
			expect( getProductVariations ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getProductVariations is not called if selected product has no variations', () => {
			TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
					selected={ [ 2 ] }
					showVariations={ true }
				/>
			);
			const { getProductVariations } = mockUtils;

			expect( getProductVariations ).toHaveBeenCalledTimes( 0 );
		} );

		it( 'getProductVariations is called if selected product is a variation', () => {
			TestRenderer.create(
				<TestComponent
					error={ null }
					isLoading={ false }
					products={ mockProducts }
					selected={ [ 3 ] }
					showVariations={ true }
				/>
			);
			const { getProductVariations } = mockUtils;

			expect( getProductVariations ).toHaveBeenCalledWith( 1 );
			expect( getProductVariations ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'when the API returns variations data', () => {
		beforeEach( () => {
			mockUtils.getProductVariations.mockImplementation( () =>
				Promise.resolve( mockVariations )
			);
			renderer = render();
		} );

		it( 'sets the variations props', () => {
			const props = renderer.root.findByType( 'div' ).props;
			const expectedVariations = {
				1: [
					{ id: 3, name: 'Blue', parent: 1 },
					{ id: 4, name: 'Red', parent: 1 },
				],
			};

			expect( props.error ).toBeNull();
			expect( props.isLoading ).toBe( false );
			expect( props.variations ).toEqual( expectedVariations );
		} );
	} );

	describe( 'when the API returns an error', () => {
		const error = { message: 'There was an error.' };
		const getProductVariationsPromise = Promise.reject( error );
		const formattedError = { message: 'There was an error.', type: 'api' };

		beforeEach( () => {
			mockUtils.getProductVariations.mockImplementation(
				() => getProductVariationsPromise
			);
			mockBaseUtils.formatError.mockImplementation(
				() => formattedError
			);
			renderer = render();
		} );

		it( 'sets the error prop', ( done ) => {
			const { formatError } = mockBaseUtils;
			getProductVariationsPromise.catch( () => {
				const props = renderer.root.findByType( 'div' ).props;

				expect( formatError ).toHaveBeenCalledWith( error );
				expect( formatError ).toHaveBeenCalledTimes( 1 );
				expect( props.error ).toEqual( formattedError );
				expect( props.isLoading ).toBe( false );
				expect( props.variations ).toEqual( { 1: null } );

				done();
			} );
		} );
	} );
} );
