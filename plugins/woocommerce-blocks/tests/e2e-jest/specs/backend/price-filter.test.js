/**
 * External dependencies
 */
import {
	switchBlockInspectorTab,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';
import {
	visitBlockPage,
	selectBlockByName,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { openSettingsSidebar } from '../../utils.js';

const block = {
	name: 'Filter by Price',
	slug: 'woocommerce/price-filter',
	class: '.wp-block-woocommerce-price-filter',
};

describe( `${ block.name } Block`, () => {
	describe( 'after compatibility notice is dismissed', () => {
		beforeAll( async () => {
			await switchUserToAdmin();
			await visitBlockPage( `${ block.name } Block` );
		} );

		it( 'renders without crashing', async () => {
			await expect( page ).toRenderBlock( block );
		} );

		describe( 'Attributes', () => {
			beforeEach( async () => {
				await openSettingsSidebar();
				await selectBlockByName( block.slug );
				await switchBlockInspectorTab( 'Settings' );
			} );

			it( "allows changing the block's title", async () => {
				const textareaSelector =
					'.wp-block-woocommerce-filter-wrapper .wp-block-heading';

				await expect( page ).toFill( textareaSelector, 'New Title' );

				await page.click( block.class );

				await expect( page ).toMatchElement( textareaSelector, {
					text: 'New Title',
				} );

				await expect( page ).toFill(
					textareaSelector,
					'Filter by price'
				);
			} );

			it( 'allows changing the Display Style', async () => {
				// Turn the display style to Price Range: Text
				await expect( page ).toClick( 'button', { text: 'Text' } );

				await page.waitForSelector(
					'.wc-block-price-filter__range-text'
				);
				await expect( page ).toMatchElement(
					'.wc-block-price-filter__range-text'
				);
				// Turn the display style to Price Range: Editable
				await expect( page ).toClick( 'button', {
					text: 'Editable',
				} );

				await expect( page ).not.toMatchElement(
					'.wc-block-price-filter__range-text'
				);
			} );

			it( 'allows you to toggle filter button', async () => {
				await expect( page ).toClick( 'label', {
					text: "Show 'Apply filters' button",
				} );
				await expect( page ).toMatchElement(
					'button.wc-block-filter-submit-button.wc-block-price-filter__button'
				);
				await expect( page ).toClick( 'label', {
					text: "Show 'Apply filters' button",
				} );
			} );
		} );
	} );
} );
