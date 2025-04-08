/**
 * Internal dependencies
 */
import { getSegmentsFromPath } from '../url-helpers';

describe( 'URL Helpers', () => {
	it( 'should extract segments from query path param correctly', () => {
		const paths = [
			{ input: undefined, output: [] },
			{ input: '', output: [ '' ] },
			{ input: 'product', output: [ 'product' ] },
			{ input: 'product/', output: [ 'product' ] },
			{ input: '/product', output: [ 'product' ] },
			{ input: '/product/', output: [ 'product' ] },
			{ input: 'product/123', output: [ 'product', '123' ] },
			{ input: 'product/123/', output: [ 'product', '123' ] },
			{ input: '/product/123', output: [ 'product', '123' ] },
			{ input: '/product/123/', output: [ 'product', '123' ] },
		];

		paths.forEach( ( { input, output } ) => {
			expect( getSegmentsFromPath( input ) ).toStrictEqual( output );
		} );
	} );
} );
