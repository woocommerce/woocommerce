/**
 * External dependencies
 */
import {
	switchUserToAdmin,
	getEditedPostContent,
	openDocumentSettingsSidebar,
} from '@wordpress/e2e-test-utils';
import { clearAndFillInput } from '@woocommerce/e2e-utils';
import {
	findLabelWithText,
	visitBlockPage,
} from '@woocommerce/blocks-test-utils';

const block = {
	name: 'Product Search',
	slug: 'woocommerce/product-search',
	class: '.wc-block-product-search',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	it( 'can toggle field label', async () => {
		await openDocumentSettingsSidebar();
		await page.click( block.class );
		const selector = `${ block.class } .wc-block-product-search__label`;
		const toggleLabel = await findLabelWithText(
			'Show search field label'
		);
		await expect( toggleLabel ).toToggleElement( selector );
	} );

	it( 'can change field labels in editor', async () => {
		await expect( page ).toFill(
			'textarea.wc-block-product-search__label',
			'I am a new label'
		);

		await expect( page ).toFill(
			'.wc-block-product-search__field input',
			'I am a new placeholder'
		);

		await clearAndFillInput(
			'textarea.wc-block-product-search__label',
			'The Label'
		);
		await clearAndFillInput(
			'.wc-block-product-search__field input',
			'The Placeholder'
		);

		expect( await getEditedPostContent() ).toMatchSnapshot();
	} );
} );
