/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, fireEvent, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../tests/integration/helpers/integration-test-editor';
import '../';
import '../quantity-selector';
import '../../../atomic/blocks/product-elements/button';
import '../../../atomic/blocks/product-elements/stock-indicator';
import '../../../atomic/blocks/product-elements/price';
import '../grouped-product-selector';
import '../grouped-product-selector/product-item';
import '../grouped-product-selector/product-item-label';
import '../grouped-product-selector/product-item-selector';
import '../variation-selector';
import '../variation-selector/attribute';
import '../variation-selector/attribute-name';
import '../variation-selector/attribute-options';
import '../variation-description';

const mockSimpleTemplatePartHTML = `<!-- wp:woocommerce/product-stock-indicator {"style":{"spacing":{"margin":{"top":"1rem","bottom":"1rem"}}}} /-->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:woocommerce/add-to-cart-with-options-quantity-selector {"quantitySelectorStyle":"stepper"} /-->
<!-- wp:woocommerce/product-button {"textAlign":"left"} /--></div>
<!-- /wp:group -->`;
const mockExternalTemplatePartHTML = `<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:woocommerce/product-button {"textAlign":"left"} /--></div>
<!-- /wp:group -->`;
const mockGroupedTemplatePartHTML = `<!-- wp:woocommerce/add-to-cart-with-options-grouped-product-selector -->
<div
	class="wp-block-woocommerce-add-to-cart-with-options-grouped-product-selector"
	role="list"
>
	<!-- wp:woocommerce/add-to-cart-with-options-grouped-product-item -->
	<div
		class="wp-block-woocommerce-add-to-cart-with-options-grouped-product-item"
		role="listitem"
	>
		<!-- wp:group {"style":{"spacing":{"margin":{"top":"1rem","bottom":"1rem"}}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":null}} -->
		<div
			class="wp-block-group"
			style="margin-top: 1rem; margin-bottom: 1rem"
		>
			<!-- wp:woocommerce/add-to-cart-with-options-grouped-product-item-selector /-->

			<!-- wp:woocommerce/add-to-cart-with-options-grouped-product-item-label {"style":{"layout":{"selfStretch":"fill"},"spacing":{"margin":{"top":"0","bottom":"0"}},"typography":{"fontWeight":400}}} /-->

			<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"right"}} -->
			<div class="wp-block-group">
				<!-- wp:woocommerce/product-price {"textAlign":"right","isDescendentOfSingleProductTemplate":true,"isDescendentOfSingleProductBlock":true,"style":{"typography":{"fontWeight":400,"fontStyle":"normal"}}} /-->
				<!-- wp:woocommerce/product-stock-indicator {"fontSize":"small"} /--></div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:woocommerce/add-to-cart-with-options-grouped-product-item -->
</div>
<!-- /wp:woocommerce/add-to-cart-with-options-grouped-product-selector -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:woocommerce/product-button {"textAlign":"left"} /--></div>
<!-- /wp:group -->`;
const mockVariableTemplatePartHTML = `<!-- wp:woocommerce/add-to-cart-with-options-variation-selector -->
<div
	class="wp-block-woocommerce-add-to-cart-with-options-variation-selector"
	role="list"
>
	<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute -->
	<div
		class="wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute"
		role="listitem"
	>
		<!-- wp:group {"style":{"spacing":{"blockGap":"0.5rem","margin":{"top":"1rem","bottom":"1rem"}}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
		<div
			class="wp-block-group"
			style="margin-top: 1rem; margin-bottom: 1rem"
		>
			<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-name /-->

			<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-options /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:woocommerce/add-to-cart-with-options-variation-selector-attribute -->
</div>
<!-- /wp:woocommerce/add-to-cart-with-options-variation-selector -->
<!-- wp:woocommerce/add-to-cart-with-options-variation-description {"style":{"spacing":{"margin":{"top":"1rem","bottom":"1rem"}}}} /-->
<!-- wp:woocommerce/product-stock-indicator {"style":{"spacing":{"margin":{"top":"1rem","bottom":"1rem"}}}} /-->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group">
	<!-- wp:woocommerce/add-to-cart-with-options-quantity-selector {"quantitySelectorStyle":"stepper"} /-->

	<!-- wp:woocommerce/product-button {"textAlign":"left"} /-->
</div>
<!-- /wp:group -->
`;

jest.mock( '@woocommerce/settings', () => {
	return {
		...jest.requireActual( '@woocommerce/settings' ),
		getSetting: jest.fn().mockImplementation( ( key, defaultValue ) => {
			if ( key === 'productTypes' ) {
				return {
					simple: 'Simple product',
					external: 'External/Affiliate product',
					grouped: 'Grouped product',
					variable: 'Variable product',
				};
			}
			if ( key === 'addToCartWithOptionsTemplatePartIds' ) {
				return {
					simple: 'woocommerce/woocommerce//simple-product-add-to-cart-with-options',
					external:
						'woocommerce/woocommerce//external-product-add-to-cart-with-options',
					grouped:
						'woocommerce/woocommerce//grouped-product-add-to-cart-with-options',
					variable:
						'woocommerce/woocommerce//variable-product-add-to-cart-with-options',
				};
			}
			return defaultValue;
		} ),
	};
} );

const mockProduct = {
	id: 82,
	name: 'Beanie with Logo',
	slug: 'beanie-with-logo',
	parent: 0,
	type: 'simple',
	variation: '',
	permalink: 'https://202508.local/product/beanie-with-logo/',
	sku: 'Woo-beanie-logo',
	short_description: '<p>This is a simple product.</p>',
	description:
		'<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>',
	on_sale: true,
	prices: {
		price: '1800',
		regular_price: '2000',
		sale_price: '1800',
		price_range: null,
		currency_code: 'EUR',
		currency_symbol: '\u20ac',
		currency_minor_unit: 2,
		currency_decimal_separator: ',',
		currency_thousand_separator: '.',
		currency_prefix: '',
		currency_suffix: ' \u20ac',
	},
	price_html:
		'<del aria-hidden="true"><span class="woocommerce-Price-amount amount">20,00&nbsp;<span class="woocommerce-Price-currencySymbol">&euro;</span></span></del> <span class="screen-reader-text">Original price was: 20,00&nbsp;&euro;.</span><ins aria-hidden="true"><span class="woocommerce-Price-amount amount">18,00&nbsp;<span class="woocommerce-Price-currencySymbol">&euro;</span></span></ins><span class="screen-reader-text">Current price is: 18,00&nbsp;&euro;.</span>',
	average_rating: '0',
	review_count: 0,
	images: [
		{
			id: 105,
			src: 'https://202508.local/wp-content/uploads/2025/09/beanie-with-logo-1.jpg',
			thumbnail:
				'https://202508.local/wp-content/uploads/2025/09/beanie-with-logo-1-300x300.jpg',
			srcset: 'https://202508.local/wp-content/uploads/2025/09/beanie-with-logo-1.jpg 800w, https://202508.local/wp-content/uploads/2025/09/beanie-with-logo-1-300x300.jpg 300w, https://202508.local/wp-content/uploads/2025/09/beanie-with-logo-1-100x100.jpg 100w, https://202508.local/wp-content/uploads/2025/09/beanie-with-logo-1-600x600.jpg 600w, https://202508.local/wp-content/uploads/2025/09/beanie-with-logo-1-150x150.jpg 150w, https://202508.local/wp-content/uploads/2025/09/beanie-with-logo-1-768x768.jpg 768w',
			sizes: '(max-width: 800px) 100vw, 800px',
			name: 'beanie-with-logo-1.jpg',
			alt: '',
		},
	],
	categories: [
		{
			id: 19,
			name: 'Accessories',
			slug: 'accessories',
			link: 'https://202508.local/product-category/clothing/accessories/',
		},
	],
	tags: [],
	brands: [],
	attributes: [
		{
			id: 1,
			name: 'Color',
			taxonomy: 'pa_color',
			has_variations: false,
			terms: [ { id: 24, name: 'Red', slug: 'red' } ],
		},
	],
	variations: [],
	grouped_products: [],
	has_options: false,
	is_purchasable: true,
	is_in_stock: true,
	is_on_backorder: false,
	low_stock_remaining: null,
	stock_availability: { text: '', class: 'in-stock' },
	sold_individually: false,
	add_to_cart: {
		text: 'Add to cart',
		description: 'Add to cart: &ldquo;Beanie with Logo&rdquo;',
		url: '/wp-json/wc/store/v1/products?_locale=user&#038;add-to-cart=82',
		single_text: 'Add to cart',
		minimum: 1,
		maximum: 9999,
		multiple_of: 1,
	},
};

// Setup MSW.
const handlers = [
	http.get( '/wp/v2/types', () => {
		return HttpResponse.json( {
			wp_template_part: {
				slug: 'wp_template_part',
				rest_base: 'template-parts',
				rest_namespace: 'wp/v2',
			},
		} );
	} ),

	http.get( '/wc/v3/products', () => {
		return HttpResponse.json( [ mockProduct ] );
	} ),

	http.get( '/wc/store/v1/products/:id', () => {
		return HttpResponse.json( mockProduct );
	} ),

	http.get( '/wc/v3/products/:id', () => {
		return HttpResponse.json( mockProduct );
	} ),

	http.get( '/wp/v2/template-parts/*', ( request ) => {
		if (
			request.params[ 0 ] ===
			'woocommerce/woocommerce//simple-product-add-to-cart-with-options'
		) {
			return HttpResponse.json( {
				id: 'woocommerce/woocommerce//simple-product-add-to-cart-with-options',
				content: {
					raw: mockSimpleTemplatePartHTML,
				},
			} );
		}
		if (
			request.params[ 0 ] ===
			'woocommerce/woocommerce//external-product-add-to-cart-with-options'
		) {
			return HttpResponse.json( {
				id: 'woocommerce/woocommerce//external-product-add-to-cart-with-options',
				content: {
					raw: mockExternalTemplatePartHTML,
				},
			} );
		}
		if (
			request.params[ 0 ] ===
			'woocommerce/woocommerce//grouped-product-add-to-cart-with-options'
		) {
			return HttpResponse.json( {
				id: 'woocommerce/woocommerce//grouped-product-add-to-cart-with-options',
				content: {
					raw: mockGroupedTemplatePartHTML,
				},
			} );
		}

		if (
			request.params[ 0 ] ===
			'woocommerce/woocommerce//variable-product-add-to-cart-with-options'
		) {
			return HttpResponse.json( {
				id: 'woocommerce/woocommerce//variable-product-add-to-cart-with-options',
				content: {
					raw: mockVariableTemplatePartHTML,
				},
			} );
		}
	} ),
];

const server = setupServer( ...handlers );

// Start MSW.
beforeAll( () => server.listen() );
afterEach( () => server.resetHandlers() );
afterAll( () => server.close() );

async function setup() {
	const addToCartWithOptionsBlock = [
		{
			name: 'woocommerce/add-to-cart-with-options',
		},
	];
	return await initializeEditor( addToCartWithOptionsBlock );
}

async function switchProductType( productType: string ) {
	await selectBlock( 'Block: Add to Cart + Options (Beta)' );

	await act( async () => {
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Switch product type' } )
		);
	} );

	await act( async () => {
		fireEvent.click(
			screen.getByRole( 'menuitem', { name: productType } )
		);
	} );
}

describe( 'Add to Cart + Options block', () => {
	it( 'should render inner blocks', async () => {
		await setup();

		const block = screen.getByRole( 'document', {
			name: 'Block: Add to Cart + Options (Beta)',
		} );
		expect( block ).toBeInTheDocument();

		// Simple products.
		await waitFor( () => {
			expect(
				screen.getByRole( 'document', {
					name: 'Block: Product Stock Indicator',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Product Quantity (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Add to Cart Button',
				} )
			).toBeInTheDocument();
		} );

		// External products.
		await switchProductType( 'External/Affiliate product' );

		await waitFor( () => {
			expect(
				screen.queryByRole( 'document', {
					name: 'Block: Product Stock Indicator',
				} )
			).not.toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Add to Cart Button',
				} )
			).toBeInTheDocument();
		} );

		// Grouped products.
		await switchProductType( 'Grouped product' );

		await waitFor( () => {
			expect(
				screen.queryByRole( 'document', {
					name: 'Block: Grouped Product Selector (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Grouped Product: Template (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Grouped Product: Item Selector (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Grouped Product: Item Label (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Product Price',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Product Stock Indicator',
				} )
			).toBeInTheDocument();
		} );

		// Variable products.
		await switchProductType( 'Variable product' );

		await waitFor( () => {
			expect(
				screen.queryByRole( 'document', {
					name: 'Block: Variation Selector (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'list', {
					name: 'Block: Variation Selector: Template (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen
					.getAllByRole( 'document', {
						name: 'Block: Variation Selector: Attribute Name (Beta)',
					} )
					.at( 0 )
			).toBeInTheDocument();

			expect(
				screen
					.getAllByRole( 'document', {
						name: 'Block: Variation Selector: Attribute Options (Beta)',
					} )
					.at( 0 )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Variation Description (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Product Stock Indicator',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Product Quantity (Beta)',
				} )
			).toBeInTheDocument();

			expect(
				screen.getByRole( 'document', {
					name: 'Block: Add to Cart Button',
				} )
			).toBeInTheDocument();
		} );
	} );
} );

// screen.debug( undefined, 300000 );
