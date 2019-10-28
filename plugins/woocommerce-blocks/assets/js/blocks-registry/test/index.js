/**
 * Internal dependencies
 */
import { getRegisteredInnerBlocks, registerInnerBlock } from '../index';

describe( 'blocks registry', () => {
	const main = '@woocommerce/all-products';
	const blockName = '@woocommerce-extension/price-level';
	const component = () => {};

	describe( 'registerInnerBlock', () => {
		const invokeTest = ( args ) => () => {
			return registerInnerBlock( args );
		};
		it( 'throws an error when registered block is missing `main`', () => {
			expect( invokeTest( { main: null } ) ).toThrowError( /main/ );
		} );
		it( 'throws an error when registered block is missing `blockName`', () => {
			expect( invokeTest( { main, blockName: null } ) ).toThrowError(
				/blockName/
			);
		} );
		it( 'throws an error when registered block is missing `component`', () => {
			expect(
				invokeTest( { main, blockName, component: null } )
			).toThrowError( /component/ );
		} );
	} );

	describe( 'getRegisteredInnerBlocks', () => {
		it( 'gets an empty object when parent has no inner blocks', () => {
			expect(
				getRegisteredInnerBlocks( '@woocommerce/all-products' )
			).toEqual( {} );
		} );
		it( 'gets a block that was successfully registered', () => {
			registerInnerBlock( { main, blockName, component } );
			expect(
				getRegisteredInnerBlocks( '@woocommerce/all-products' )
			).toEqual( { [ blockName ]: component } );
		} );
	} );
} );
