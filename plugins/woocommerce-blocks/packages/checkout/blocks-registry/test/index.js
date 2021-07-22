/**
 * Internal dependencies
 */
import { getRegisteredBlocks, registerCheckoutBlock } from '../index';

describe( 'checkout blocks registry', () => {
	const component = () => {
		return null;
	};

	describe( 'registerCheckoutBlock', () => {
		const invokeTest = ( blockName, options ) => () => {
			return registerCheckoutBlock( blockName, options );
		};
		it( 'throws an error when registered block is missing `blockName`', () => {
			expect(
				invokeTest( null, { areas: [ 'fields' ], component } )
			).toThrowError( /blockName/ );
			expect(
				invokeTest( '', { areas: [ 'fields' ], component } )
			).toThrowError( /blockName/ );
		} );
		it( 'throws an error when area is invalid', () => {
			expect(
				invokeTest( 'test/block-name', {
					areas: [ 'invalid-area' ],
					component,
				} )
			).toThrowError( /area/ );
		} );
		it( 'throws an error when registered block is missing `component`', () => {
			expect(
				invokeTest( 'test/block-name', {
					areas: [ 'fields' ],
					component: null,
				} )
			).toThrowError( /component/ );
		} );
	} );

	describe( 'getRegisteredBlocks', () => {
		const invokeTest = ( areas ) => () => {
			return getRegisteredBlocks( areas );
		};
		it( 'gets an empty array when checkout area has no registered blocks', () => {
			expect( getRegisteredBlocks( 'fields' ) ).toEqual( [] );
		} );
		it( 'throws an error if the area is not defined', () => {
			expect( invokeTest( 'non-existent-area' ) ).toThrowError( /area/ );
		} );
		it( 'gets a block that was successfully registered', () => {
			registerCheckoutBlock( 'test/block-name', {
				areas: [ 'fields' ],
				component,
			} );
			expect( getRegisteredBlocks( 'fields' ) ).toEqual( [
				'test/block-name',
			] );
		} );
	} );
} );
