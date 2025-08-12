/**
 * External dependencies
 */
import { registerCoreBlocks } from '@wordpress/block-library';

/**
 * Internal dependencies
 */
import { createCrossSellsProductCollection } from '../index';
import crossSells from '../../../../product-collection/collections/cross-sells';
import '../../../../../atomic/blocks/product-elements/sale-badge/index';
import '../../../../../atomic/blocks/product-elements/image/index';
import '../../../../../atomic/blocks/product-elements/price/index';
import '../../../../../atomic/blocks/product-elements/button/index';
import '../../../../../atomic/blocks/product-elements/title/index';
import '../../../../product-template/index';
import '../../../../product-collection/index';

// Register core blocks and WooCommerce blocks needed for the test
beforeAll( () => {
	registerCoreBlocks();
} );

describe( 'createCrossSellsProductCollection transform function', () => {
	it( 'transforms to product-collection block with cross-sells attributes', () => {
		const transformedBlock = createCrossSellsProductCollection();

		// Test block type and collection identifier
		expect( transformedBlock.name ).toBe(
			'woocommerce/product-collection'
		);
		expect( transformedBlock.attributes.collection ).toBe(
			'woocommerce/product-collection/cross-sells'
		);

		// Test that cross-sells attributes are preserved exactly
		expect( transformedBlock.attributes.displayLayout ).toEqual(
			crossSells.attributes.displayLayout
		);
		expect( transformedBlock.attributes.query ).toEqual(
			crossSells.attributes.query
		);
		expect( transformedBlock.attributes.hideControls ).toEqual(
			crossSells.attributes.hideControls
		);
	} );

	it( 'creates inner blocks from cross-sells template', () => {
		const transformedBlock = createCrossSellsProductCollection();

		expect( transformedBlock.innerBlocks.length ).toBeGreaterThan( 0 );

		const headingBlock = transformedBlock.innerBlocks.find(
			( block ) => block.name === 'core/heading'
		);
		expect( headingBlock ).toBeDefined();
		if ( ! headingBlock ) return;

		expect( headingBlock.attributes.level ).toBe( 2 );
		expect( headingBlock.attributes.content ).toBeDefined();
		expect( headingBlock.attributes.textAlign ).toBe( 'left' );
	} );
} );
