/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { deleteAllTemplates } from '@wordpress/e2e-test-utils';
import {
	installPluginFromPHPFile,
	uninstallPluginFromPHPFile,
} from '@woocommerce/e2e-mocks/custom-plugins';

/**
 * Internal dependencies
 */
import ProductCollectionPage from './product-collection.page';

type Scenario = {
	title: string;
	dataTestId: string;
	content: string;
	amount: number;
};

const singleOccurranceScenarios: Scenario[] = [
	{
		title: 'Before Main Content',
		dataTestId: 'woocommerce_before_main_content',
		content: 'Hook: woocommerce_before_main_content',
		amount: 1,
	},
	{
		title: 'After Main Content',
		dataTestId: 'woocommerce_after_main_content',
		content: 'Hook: woocommerce_after_main_content',
		amount: 1,
	},
	{
		title: 'Before Shop Loop',
		dataTestId: 'woocommerce_before_shop_loop',
		content: 'Hook: woocommerce_before_shop_loop',
		amount: 1,
	},
	{
		title: 'After Shop Loop',
		dataTestId: 'woocommerce_after_shop_loop',
		content: 'Hook: woocommerce_after_shop_loop',
		amount: 1,
	},
];

const multipleOccurranceScenarios: Scenario[] = [
	{
		title: 'Before Shop Loop Item Title',
		dataTestId: 'woocommerce_before_shop_loop_item_title',
		content: 'Hook: woocommerce_before_shop_loop_item_title',
		amount: 16,
	},
	{
		title: 'Shop Loop Item Title',
		dataTestId: 'woocommerce_shop_loop_item_title',
		content: 'Hook: woocommerce_shop_loop_item_title',
		amount: 16,
	},
	{
		title: 'After Shop Loop Item Title',
		dataTestId: 'woocommerce_after_shop_loop_item_title',
		content: 'Hook: woocommerce_after_shop_loop_item_title',
		amount: 16,
	},
	{
		title: 'Before Shop Loop Item',
		dataTestId: 'woocommerce_before_shop_loop_item',
		content: 'Hook: woocommerce_before_shop_loop_item',
		amount: 16,
	},
	{
		title: 'After Shop Loop Item',
		dataTestId: 'woocommerce_after_shop_loop_item',
		content: 'Hook: woocommerce_after_shop_loop_item',
		amount: 16,
	},
];

const compatiblityPluginFileName = 'compatibility-plugin.php';
const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async (
		{ page, admin, editor, templateApiUtils, editorUtils },
		use
	) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
			templateApiUtils,
			editorUtils,
		} );
		await pageObject.createNewPostAndInsertBlock();
		await use( pageObject );
	},
} );

test.describe( 'Compatibility Layer with Product Collection block', () => {
	test.beforeAll( async () => {
		await installPluginFromPHPFile(
			`${ __dirname }/${ compatiblityPluginFileName }`
		);
	} );

	test.describe(
		'Product Archive with Product Collection block',
		async () => {
			test.beforeEach( async ( { pageObject } ) => {
				await pageObject.replaceProductsWithProductCollectionInTemplate(
					'woocommerce/woocommerce//archive-product'
				);
				await pageObject.goToProductCatalogFrontend();
			} );

			test( 'Hooks are attached to the page', async ( {
				pageObject,
			} ) => {
				singleOccurranceScenarios.forEach(
					async ( { title, dataTestId, content, amount } ) => {
						await test.step( title, async () => {
							const hooks =
								pageObject.locateByTestId( dataTestId );
							await expect( hooks ).toHaveCount( amount );
							await expect( hooks ).toHaveText( content );
						} );
					}
				);

				multipleOccurranceScenarios.forEach(
					async ( { title, dataTestId, content, amount } ) => {
						await test.step( title, async () => {
							const hooks =
								pageObject.locateByTestId( dataTestId );
							await expect( hooks ).toHaveCount( amount );
							await expect( hooks.first() ).toHaveText( content );
						} );
					}
				);
			} );
		}
	);

	test.afterAll( async () => {
		await uninstallPluginFromPHPFile(
			`${ __dirname }/${ compatiblityPluginFileName }`
		);
		await deleteAllTemplates( 'wp_template' );
	} );
} );
