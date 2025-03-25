/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import '@testing-library/jest-dom';
import { fireEvent, screen, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../tests/integration/helpers/integration-test-editor';
import '../';

async function setup( attributes: BlockAttributes ) {
	const testBlock = [ { name: 'woocommerce/active-filters', attributes } ];
	return initializeEditor( testBlock );
}

describe( 'Active Filters block', () => {
	describe( 'Display settings', () => {
		test( 'can change the display style', async () => {
			await setup( {} );

			const activeFiltersBlock = within(
				screen.getByLabelText( /Block: Active Filters/i )
			);
			const filterList = activeFiltersBlock.getByRole( 'list' );
			expect( filterList ).toHaveClass( 'wc-block-active-filters__list' );
			expect( filterList ).not.toHaveClass(
				'wc-block-active-filters__list--chips'
			);

			await selectBlock( /Block: Active Filters/i );

			const displaySettings = screen.getByRole( 'button', {
				name: /Display Settings/i,
			} );

			if ( displaySettings.getAttribute( 'aria-expanded' ) !== 'true' ) {
				fireEvent.click( displaySettings );
			}

			fireEvent.click( screen.getByRole( 'radio', { name: /Chips/i } ) );
			expect( filterList ).toHaveClass( 'wc-block-active-filters__list' );
			expect( filterList ).toHaveClass(
				'wc-block-active-filters__list--chips'
			);
		} );
	} );
} );
