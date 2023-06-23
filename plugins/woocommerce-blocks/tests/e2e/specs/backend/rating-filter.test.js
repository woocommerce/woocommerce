/**
 * External dependencies
 */
import {
	switchBlockInspectorTab,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { openSettingsSidebar } from '../../utils';

const block = {
	name: 'Filter by Rating',
	slug: 'woocommerce/rating-filter',
	class: '.wc-block-rating-filter',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	describe( 'attributes', () => {
		beforeEach( async () => {
			await openSettingsSidebar();
			await page.click( block.class );
			await switchBlockInspectorTab( 'Settings' );
		} );

		it( 'product count can be toggled', async () => {
			await expect( page ).not.toMatchElement(
				'.wc-block-components-product-rating-count'
			);
			await expect( page ).toClick( 'label', {
				text: 'Display product count',
			} );
			await expect( page ).toMatchElement(
				'.wc-block-components-product-rating-count'
			);
			// reset
			await expect( page ).toClick( 'label', {
				text: 'Display product count',
			} );
		} );

		it( 'filter button can be toggled', async () => {
			await expect( page ).not.toMatchElement(
				'button.wc-block-filter-submit-button.wc-block-rating-filter__button'
			);
			await expect( page ).toClick( 'label', {
				text: "Show 'Apply filters' button",
			} );
			await expect( page ).toMatchElement(
				'button.wc-block-filter-submit-button.wc-block-rating-filter__button'
			);
			// reset
			await expect( page ).toClick( 'label', {
				text: "Show 'Apply filters' button",
			} );
		} );

		it( 'allows changing the Display Style', async () => {
			// Turn the display style to Dropdown
			await expect( page ).toClick( 'button', { text: 'Dropdown' } );

			await page.waitForSelector(
				'.wc-block-rating-filter.style-dropdown'
			);
			await expect( page ).toMatchElement(
				'.wc-block-rating-filter.style-dropdown'
			);
			// Turn the display style to List
			await expect( page ).toClick( 'button', {
				text: 'List',
			} );

			await page.waitForSelector( '.wc-block-rating-filter.style-list' );
			await expect( page ).toMatchElement(
				'.wc-block-rating-filter.style-list'
			);
		} );
	} );
} );
