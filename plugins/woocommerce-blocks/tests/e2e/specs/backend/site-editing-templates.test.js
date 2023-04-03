/* eslint-disable jest/no-conditional-expect */
/**
 * External dependencies
 */
import { URL } from 'url';
import {
	canvas,
	deleteAllTemplates,
	getCurrentSiteEditorContent,
	insertBlock,
} from '@wordpress/e2e-test-utils';
import {
	getNormalPagePermalink,
	visitPostOfType,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import {
	BASE_URL,
	DEFAULT_TIMEOUT,
	filterCurrentBlocks,
	getAllTemplates,
	goToTemplateEditor,
	goToTemplatesList,
	saveTemplate,
	useTheme,
} from '../../utils';

async function visitTemplateAndAddCustomParagraph(
	templateSlug,
	customText = CUSTOMIZED_STRING
) {
	await goToTemplateEditor( {
		postId: `woocommerce/woocommerce//${ templateSlug }`,
	} );

	await insertBlock( 'Paragraph' );
	await page.keyboard.type( customText );
	await saveTemplate();
}

function blockSelector( id ) {
	return `[data-type="${ id }"]`;
}

function defaultTemplateProps( templateTitle ) {
	return {
		templateTitle,
		addedBy: WOOCOMMERCE_ID,
		hasActions: false,
	};
}

function classicBlockSelector( title ) {
	return `${ blockSelector(
		'woocommerce/legacy-template'
	) }[data-title="${ title }"]`;
}

const BLOCK_DATA = {
	'archive-product': {
		attributes: {
			placeholder: 'archive-product',
			template: 'archive-product',
			title: 'WooCommerce Product Grid Block',
		},
		name: 'woocommerce/legacy-template',
	},
	'single-product': {
		attributes: {
			placeholder: 'single-product',
			template: 'single-product',
			title: 'WooCommerce Single Product Block',
		},
		name: 'woocommerce/legacy-template',
	},
	'taxonomy-product_cat': {
		attributes: {
			placeholder: 'archive-product',
			template: 'taxonomy-product_cat',
			title: 'WooCommerce Product Taxonomy Block',
		},
		name: 'woocommerce/legacy-template',
	},
	'taxonomy-product_tag': {
		attributes: {
			placeholder: 'archive-product',
			template: 'taxonomy-product_tag',
			title: 'WooCommerce Product Tag Block',
		},
		name: 'woocommerce/legacy-template',
	},
	'taxonomy-product_attribute': {
		attributes: {
			placeholder: 'archive-product',
			template: 'taxonomy-product_attribute',
			title: 'WooCommerce Product Attribute Block',
		},
		name: 'woocommerce/legacy-template',
	},
	'product-search-results': {
		attributes: {
			title: 'WooCommerce Product Search Results Block',
			template: 'product-search-results',
			placeholder: 'archive-product',
		},
		name: 'woocommerce/legacy-template',
	},
};

const SELECTORS = {
	blocks: {
		paragraph: blockSelector( 'core/paragraph' ),
		productArchive: classicBlockSelector(
			'WooCommerce Product Grid Block'
		),
		singleProduct: classicBlockSelector(
			'WooCommerce Single Product Block'
		),
	},
	templates: {
		templateActions:
			'[aria-label="Templates list - Content"] [aria-label="Actions"]',
	},
};

const CUSTOMIZED_STRING = 'My awesome customization';
const WOOCOMMERCE_ID = 'woocommerce/woocommerce';
const WOOCOMMERCE_PARSED_ID = 'woocommerce/woocommerceCustomized';

describe( 'Store Editing Templates', () => {
	useTheme( 'emptytheme' );

	describe( 'Single Product block template', () => {
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps( 'Single Product' );

			await goToTemplatesList();

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should contain the "WooCommerce Single Product Block" classic template', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//single-product',
			} );

			const [ classicBlock ] = await filterCurrentBlocks(
				( block ) => block.name === BLOCK_DATA[ 'single-product' ].name
			);

			// Comparing only the `template` property currently
			// because the other properties seem to be slightly unreliable.
			// Investigation pending.
			expect( classicBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'single-product' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Single Product' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph( 'single-product' );

			await goToTemplatesList( { waitFor: 'actions' } );

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should preserve and correctly show the user customization on the back-end', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//single-product',
			} );

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{
					text: CUSTOMIZED_STRING,
					timeout: DEFAULT_TIMEOUT,
				}
			);
		} );

		it( 'should show the user customization on the front-end', async () => {
			const exampleProductName = 'Woo Single #1';

			await visitPostOfType( exampleProductName, 'product' );
			const permalink = await getNormalPagePermalink();

			await page.goto( permalink );

			await expect( page ).toMatchElement( 'p', {
				text: CUSTOMIZED_STRING,
				timeout: DEFAULT_TIMEOUT,
			} );
		} );
	} );

	describe( 'Product Catalog block template', () => {
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps( 'Product Catalog' );

			await goToTemplatesList();

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should contain the "WooCommerce Product Grid Block" classic template', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//archive-product',
			} );

			const [ classicBlock ] = await filterCurrentBlocks(
				( block ) => block.name === BLOCK_DATA[ 'archive-product' ].name
			);

			expect( classicBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'archive-product' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Product Catalog' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph( 'archive-product' );

			await goToTemplatesList( { waitFor: 'actions' } );

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should preserve and correctly show the user customization on the back-end', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//archive-product',
			} );

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{
					text: CUSTOMIZED_STRING,
					timeout: DEFAULT_TIMEOUT,
				}
			);
		} );

		it( 'should show the user customization on the front-end', async () => {
			await page.goto( new URL( BASE_URL + '/shop' ) );

			await expect( page ).toMatchElement( 'p', {
				text: CUSTOMIZED_STRING,
				timeout: DEFAULT_TIMEOUT,
			} );
		} );
	} );

	describe( 'Product by Category block template', () => {
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps(
				'Products by Category'
			);

			await goToTemplatesList();

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should contain the "WooCommerce Product Taxonomy Block" classic template', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//taxonomy-product_cat',
			} );

			const [ classicBlock ] = await filterCurrentBlocks(
				( block ) =>
					block.name === BLOCK_DATA[ 'taxonomy-product_cat' ].name
			);

			expect( classicBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'taxonomy-product_cat' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Products by Category' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph( 'taxonomy-product_cat' );

			await goToTemplatesList( { waitFor: 'actions' } );

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should preserve and correctly show the user customization on the back-end', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//taxonomy-product_cat',
			} );

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{
					text: CUSTOMIZED_STRING,
					timeout: DEFAULT_TIMEOUT,
				}
			);
		} );

		it( 'should show the user customization on the front-end', async () => {
			await page.goto(
				new URL( '/product-category/uncategorized', BASE_URL )
			);

			await expect( page ).toMatchElement( 'p', {
				text: CUSTOMIZED_STRING,
				timeout: DEFAULT_TIMEOUT,
			} );
		} );
	} );

	describe( 'Products by Tag block template', () => {
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps( 'Products by Tag' );

			await goToTemplatesList();

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should contain the "WooCommerce Product Taxonomy Block" classic template', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//taxonomy-product_tag',
			} );

			const [ classicBlock ] = await filterCurrentBlocks(
				( block ) =>
					block.name === BLOCK_DATA[ 'taxonomy-product_tag' ].name
			);

			expect( classicBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'taxonomy-product_tag' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Products by Tag' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph( 'taxonomy-product_tag' );

			await goToTemplatesList( { waitFor: 'actions' } );

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should preserve and correctly show the user customization on the back-end', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//taxonomy-product_tag',
			} );

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{
					text: CUSTOMIZED_STRING,
					timeout: DEFAULT_TIMEOUT,
				}
			);
		} );

		it( 'should show the user customization on the front-end', async () => {
			await page.goto( new URL( '/product-tag/newest', BASE_URL ) );

			await expect( page ).toMatchElement( 'p', {
				text: CUSTOMIZED_STRING,
				timeout: DEFAULT_TIMEOUT,
			} );
		} );
	} );

	describe( 'Products by Attribute template', () => {
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps(
				'Products by Attribute'
			);

			await goToTemplatesList();

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should contain the "WooCommerce Product Taxonomy Block" classic template', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//taxonomy-product_attribute',
			} );

			const [ classicBlock ] = await filterCurrentBlocks(
				( block ) =>
					block.name ===
					BLOCK_DATA[ 'taxonomy-product_attribute' ].name
			);

			expect( classicBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'taxonomy-product_attribute' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Products by Attribute' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph(
				'taxonomy-product_attribute'
			);

			await goToTemplatesList( { waitFor: 'actions' } );

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should preserve and correctly show the user customization on the back-end', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//taxonomy-product_attribute',
			} );

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{
					text: CUSTOMIZED_STRING,
					timeout: DEFAULT_TIMEOUT,
				}
			);
		} );

		it( 'should show the user customization on the front-end', async () => {
			await page.goto( new URL( '/shade/red', BASE_URL ) );

			await expect( page ).toMatchElement( 'p', {
				text: CUSTOMIZED_STRING,
				timeout: DEFAULT_TIMEOUT,
			} );
		} );
	} );

	describe( 'Product Search Results block template', () => {
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps(
				'Product Search Results'
			);

			await goToTemplatesList();

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should contain the "WooCommerce Product Grid Block" classic template', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//product-search-results',
			} );

			const [ classicBlock ] = await filterCurrentBlocks(
				( block ) => block.name === BLOCK_DATA[ 'archive-product' ].name
			);

			expect( classicBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'product-search-results' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Product Search Results' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph(
				'product-search-results'
			);

			await goToTemplatesList( { waitFor: 'actions' } );

			const templates = await getAllTemplates();

			try {
				expect( templates ).toContainEqual( EXPECTED_TEMPLATE );
			} catch ( ok ) {
				// Depending on the speed of the execution and whether Chrome is headless or not
				// the id might be parsed or not

				expect( templates ).toContainEqual( {
					...EXPECTED_TEMPLATE,
					addedBy: WOOCOMMERCE_PARSED_ID,
				} );
			}
		} );

		it( 'should preserve and correctly show the user customization on the back-end', async () => {
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//product-search-results',
			} );

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{
					text: CUSTOMIZED_STRING,
					timeout: DEFAULT_TIMEOUT,
				}
			);
		} );

		it( 'should show the user customization on the front-end', async () => {
			await page.goto(
				new URL( '?s=shirt&post_type=product', BASE_URL )
			);

			await expect( page ).toMatchElement( 'p', {
				text: CUSTOMIZED_STRING,
				timeout: DEFAULT_TIMEOUT,
			} );
		} );
	} );
} );
