/**
 * Internal dependencies
 */
import {
	getRegisteredBlocks,
	registerCheckoutBlock,
	innerBlockAreas,
} from '../index';

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
		it( 'gets an empty array when checkout area has no registered blocks', () => {
			expect(
				getRegisteredBlocks( innerBlockAreas.CHECKOUT_FIELDS )
			).toEqual( [] );
		} );
		it( 'gets an empty array when the area is not defined', () => {
			expect( getRegisteredBlocks( 'not-defined' ) ).toEqual( [] );
		} );
		it( 'gets a block that was successfully registered', () => {
			registerCheckoutBlock( 'test/block-name', {
				areas: [ innerBlockAreas.CHECKOUT_FIELDS ],
				component,
			} );
			expect(
				getRegisteredBlocks( innerBlockAreas.CHECKOUT_FIELDS )
			).toEqual( [
				{
					block: 'test/block-name',
					component,
					force: false,
				},
			] );
		} );
	} );
} );
