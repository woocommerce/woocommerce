import { URL } from 'url';

import {
	activateTheme,
	canvas,
	getCurrentSiteEditorContent,
	insertBlock,
	trashAllPosts,
} from '@wordpress/e2e-test-utils';
import { addQueryArgs } from '@wordpress/url';
import {
	getNormalPagePermalink,
	visitPostOfType,
} from '@woocommerce/blocks-test-utils';
import {
	BASE_URL,
	DEFAULT_TIMEOUT,
	filterCurrentBlocks,
	getAllTemplates,
	goToSiteEditor,
	saveTemplate,
	waitForCanvas,
} from '../../utils';

async function visitTemplateAndAddCustomParagraph(
	templateSlug,
	customText = CUSTOMIZED_STRING
) {
	const templateQuery = addQueryArgs( '', {
		postId: `woocommerce/woocommerce//${ templateSlug }`,
		postType: 'wp_template',
	} );

	await goToSiteEditor( templateQuery );
	await waitForCanvas();
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

function legacyBlockSelector( title ) {
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
};

const SELECTORS = {
	blocks: {
		paragraph: blockSelector( 'core/paragraph' ),
		productArchive: legacyBlockSelector( 'WooCommerce Product Grid Block' ),
		singleProduct: legacyBlockSelector(
			'WooCommerce Single Product Block'
		),
	},
};

const CUSTOMIZED_STRING = 'My awesome customization';
const WOOCOMMERCE_ID = 'woocommerce/woocommerce';
const WOOCOMMERCE_PARSED_ID = 'WooCommerce';

describe( 'Store Editing Templates', () => {
	beforeAll( async () => {
		await activateTheme( 'emptytheme' );
		await trashAllPosts( 'wp_template' );
		await trashAllPosts( 'wp_template_part' );
	} );

	afterAll( async () => {
		await activateTheme( 'twentytwentyone' );
	} );

	describe( 'Single Product block template', () => {
		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps( 'Single Product' );

			await goToSiteEditor( '?postType=wp_template' );

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

		it( 'should contain the "WooCommerce Single Product Block" legacy template', async () => {
			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//single-product',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();

			const [ legacyBlock ] = await filterCurrentBlocks(
				( block ) => block.name === BLOCK_DATA[ 'single-product' ].name
			);

			// Comparing only the `template` property currently
			// because the other properties seem to be slightly unreliable.
			// Investigation pending.
			expect( legacyBlock.attributes.template ).toBe(
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

			await goToSiteEditor( '?postType=wp_template' );
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
			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//single-product',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{ text: CUSTOMIZED_STRING, timeout: DEFAULT_TIMEOUT }
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

	describe( 'Product Archive block template', () => {
		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps( 'Product Archive' );

			await goToSiteEditor( '?postType=wp_template' );

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

		it( 'should contain the "WooCommerce Product Archive Block" legacy template', async () => {
			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//archive-product',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();

			const [ legacyBlock ] = await filterCurrentBlocks(
				( block ) => block.name === BLOCK_DATA[ 'archive-product' ].name
			);

			expect( legacyBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'archive-product' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Product Archive' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph( 'archive-product' );

			await goToSiteEditor( '?postType=wp_template' );
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
			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//archive-product',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{ text: CUSTOMIZED_STRING, timeout: DEFAULT_TIMEOUT }
			);
		} );

		it( 'should show the user customization on the front-end', async () => {
			await page.goto( new URL( '/?post_type=product', BASE_URL ) );
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

	describe( 'Product Category block template', () => {
		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps(
				'Product Category'
			);

			await goToSiteEditor( '?postType=wp_template' );

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

		it( 'should contain the "WooCommerce Product Taxonomy Block" legacy template', async () => {
			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//taxonomy-product_cat',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();

			const [ legacyBlock ] = await filterCurrentBlocks(
				( block ) =>
					block.name === BLOCK_DATA[ 'taxonomy-product_cat' ].name
			);

			expect( legacyBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'taxonomy-product_cat' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Product Category' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph( 'taxonomy-product_cat' );

			await goToSiteEditor( '?postType=wp_template' );
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
			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//taxonomy-product_cat',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{ text: CUSTOMIZED_STRING, timeout: DEFAULT_TIMEOUT }
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

	describe( 'Product Tag block template', () => {
		it( 'default template from WooCommerce Blocks is available on an FSE theme', async () => {
			const EXPECTED_TEMPLATE = defaultTemplateProps( 'Product Tag' );

			await goToSiteEditor( '?postType=wp_template' );

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

		it( 'should contain the "WooCommerce Product Taxonomy Block" legacy template', async () => {
			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//taxonomy-product_tag',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();

			const [ legacyBlock ] = await filterCurrentBlocks(
				( block ) =>
					block.name === BLOCK_DATA[ 'taxonomy-product_tag' ].name
			);

			expect( legacyBlock.attributes.template ).toBe(
				BLOCK_DATA[ 'taxonomy-product_tag' ].attributes.template
			);
			expect( await getCurrentSiteEditorContent() ).toMatchSnapshot();
		} );

		it( 'should show the action menu if the template has been customized by the user', async () => {
			const EXPECTED_TEMPLATE = {
				...defaultTemplateProps( 'Product Tag' ),
				hasActions: true,
			};

			await visitTemplateAndAddCustomParagraph( 'taxonomy-product_tag' );

			await goToSiteEditor( '?postType=wp_template' );
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
			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//taxonomy-product_tag',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();

			await expect( canvas() ).toMatchElement(
				SELECTORS.blocks.paragraph,
				{ text: CUSTOMIZED_STRING, timeout: DEFAULT_TIMEOUT }
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
} );
