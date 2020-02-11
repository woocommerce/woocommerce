/**
 * External dependencies
 */
import {
	insertBlock,
	getEditedPostContent,
	createNewPost,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';
import { clearAndFillInput } from '@woocommerce/e2e-tests/utils';

describe( 'Product Search', () => {
	beforeEach( async () => {
		await switchUserToAdmin();
		await createNewPost();
	} );

	it( 'can be created', async () => {
		await insertBlock( 'Product Search' );

		expect( await getEditedPostContent() ).toMatchSnapshot();
	} );

	it( 'can toggle field label', async () => {
		await insertBlock( 'Product Search' );
		await page.click( '.components-form-toggle__input' );

		await expect( page ).not.toMatchElement(
			'.wc-block-product-search .wc-block-product-search__label'
		);
	} );

	it( 'can change field labels in editor', async () => {
		await insertBlock( 'Product Search' );

		await expect( page ).toFill(
			'textarea.wc-block-product-search__label',
			'I am a new label'
		);

		await expect( page ).toFill(
			'textarea.wc-block-product-search__field',
			'I am a new placeholder'
		);

		await clearAndFillInput(
			'textarea.wc-block-product-search__label',
			'The Label'
		);
		await clearAndFillInput(
			'textarea.wc-block-product-search__field',
			'The Placeholder'
		);

		expect( await getEditedPostContent() ).toMatchSnapshot();
	} );
} );
