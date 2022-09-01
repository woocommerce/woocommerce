/**
 * External dependencies
 */
import { switchUserToAdmin } from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { GUTENBERG_EDITOR_CONTEXT, describeOrSkip } from '../../utils';

describeOrSkip( GUTENBERG_EDITOR_CONTEXT === 'gutenberg' )(
	'Product Search Legacy Block',
	() => {
		beforeAll( async () => {
			await switchUserToAdmin();
			await visitBlockPage( 'Product Search Legacy Block' );
		} );

		it( 'render the upgrade prompt', async () => {
			await expect( page ).toMatch(
				'This version of the Product Search block is outdated. Upgrade to continue using.'
			);
			await expect( page ).toMatch( 'Upgrade Block' );
		} );

		it( 'clicking the upgrade button convert the legacy block to core/search variation', async () => {
			await page.click( '.block-editor-warning__action button' );

			await expect( page ).toMatchElement( '.wp-block-search' );

			await expect( page ).toMatchElement( '.wp-block-search__label', {
				text: 'Search',
			} );

			await expect( page ).toMatchElement(
				'.wp-block-search__input[value="Search products…"]'
			);
		} );
	}
);
