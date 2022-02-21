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
	DEFAULT_TIMEOUT,
	filterCurrentBlocks,
	getAllTemplates,
	goToSiteEditor,
	saveTemplate,
	waitForCanvas,
} from '../../utils';

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
	'single-product': {
		attributes: {
			placeholder: 'single-product',
			template: 'single-product',
			title: 'WooCommerce Single Product Block',
		},
		name: 'woocommerce/legacy-template',
	},
};

const SELECTORS = {
	blocks: {
		paragraph: blockSelector( 'core/paragraph' ),
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

			const templateQuery = addQueryArgs( '', {
				postId: 'woocommerce/woocommerce//single-product',
				postType: 'wp_template',
			} );

			await goToSiteEditor( templateQuery );
			await waitForCanvas();
			await insertBlock( 'Paragraph' );
			await page.keyboard.type( CUSTOMIZED_STRING );
			await saveTemplate();

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
} );
