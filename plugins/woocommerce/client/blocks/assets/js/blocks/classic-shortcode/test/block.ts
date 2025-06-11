/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import '@testing-library/jest-dom';
import { act, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */

import '../';
import '../../cart';
import '../../checkout';
import '../../product-new';
import { initializeEditor } from '../../../../../tests/integration/helpers/integration-test-editor';

async function setup( attributes: BlockAttributes ) {
	const testBlock = [ { name: 'woocommerce/classic-shortcode', attributes } ];
	return initializeEditor( testBlock );
}

describe( 'Classic Shortcode block', () => {
	test( 'can convert to Cart block', async () => {
		await setup( { shortcode: 'cart' } );

		const transformButton = screen.getByRole( 'button', {
			name: /Transform into blocks/i,
		} );
		await act( async () => {
			await transformButton.click();
		} );

		expect( screen.getByLabelText( /^Block: Cart$/i ) ).toBeInTheDocument();
	} );
	test( 'can convert to Checkout block', async () => {
		await setup( { shortcode: 'checkout' } );

		const transformButton = screen.getByRole( 'button', {
			name: /Transform into blocks/i,
		} );
		await act( async () => {
			await transformButton.click();
		} );

		expect(
			screen.getByLabelText( /^Block: Checkout$/i )
		).toBeInTheDocument();
	} );
} );
