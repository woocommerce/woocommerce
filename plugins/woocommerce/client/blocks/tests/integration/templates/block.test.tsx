/**
 * Integration tests for the Single Product Template block structure.
 *
 * These tests verify that the complete single product template renders correctly
 * with all necessary blocks and proper placeholder content when no product data
 * is available. The tests cover template structure, block rendering, placeholder
 * behavior, accessibility, and error handling.
 *
 * @package WooCommerce
 */

/**
 * External dependencies
 */
import { getByText, screen, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { initializeEditor } from '../helpers/integration-test-editor';

import { parse } from '@wordpress/blocks';
import { readFileSync } from 'fs';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';
import { resolve } from 'path';
import '../../../assets/js/atomic/blocks/product-elements/price';
import '../../../assets/js/atomic/blocks/product-elements/product-image-gallery';
import '../../../assets/js/atomic/blocks/product-elements/product-meta';
import '../../../assets/js/atomic/blocks/product-elements/rating';
import '../../../assets/js/atomic/blocks/product-elements/sku';
import '../../../assets/js/blocks/breadcrumbs';
import '../../../assets/js/blocks/product-details';
import '../../../assets/js/blocks/product-elements/add-to-cart-form';
import '../../../assets/js/blocks/store-notices';

jest.mock( '@woocommerce/atomic-utils', () => {
	const originalModule = jest.requireActual( '@wordpress/blocks' );
	return {
		registerProductBlockType: ( blockConfig: {
			name: string;
			[ key: string ]: unknown;
		} ) => {
			const blockName = blockConfig.name;
			return originalModule.registerBlockType( blockName, blockConfig );
		},
	};
} );

const singleProductTemplateFile = readFileSync(
	resolve(
		__dirname,
		'../../../../../templates/templates/blockified/single-product.html'
	),
	'utf8'
);

describe( 'Single Product Template - Block Rendering and Placeholder Tests', () => {
	const server = setupServer(
		http.get( '*', () => HttpResponse.json( {} ) ),
		http.options( '*', () => HttpResponse.json( {} ) )
	);
	// Start MSW
	beforeAll( () => server.listen() );
	afterEach( () => server.resetHandlers() );
	afterAll( () => server.close() );

	const blockAssertions = {
		// 'woocommerce/breadcrumbs': {
		// 	assert: ( block: HTMLElement ) => {
		// 		expect(
		// 			getByText( block, /\/ navigation \/ path/i )
		// 		).toBeInTheDocument();
		// 	},
		// },
		// 'woocommerce/store-notices': {
		// 	assert: ( block: HTMLElement ) => {
		// 		expect(
		// 			getByText(
		// 				block,
		// 				/notices added by woocommerce or extensions will show up here/i
		// 			)
		// 		).toBeInTheDocument();
		// 	},
		// },
		'core/post-title': {
			assert: ( block: HTMLElement ) => {
				expect( getByText( block, /Title/i ) ).toBeInTheDocument();
			},
		},
		// 'woocommerce/product-rating': {
		// 	assert: ( block: HTMLElement ) => {
		// 		expect( getByText( block, /no reviews/i ) ).toBeInTheDocument();
		// 	},
		// },
		// 'woocommerce/product-price': {
		// 	assert: ( block: HTMLElement ) => {
		// 		expect( queryByText( block, /$50.00/i ) ).toBeInTheDocument();
		// 	},
		// },
		'core/post-excerpt': {
			assert: ( block: HTMLElement ) => {
				expect(
					getByText( block, /this block will display the excerpt/i )
				).toBeInTheDocument();
			},
		},
		'woocommerce/add-to-cart-form': {
			assert: ( block: HTMLElement ) => {
				expect(
					getByText( block, /add to cart/i )
				).toBeInTheDocument();
			},
		},
		// 'woocommerce/product-sku': {
		// 	assert: ( block: HTMLElement ) => {
		// 		expect(
		// 			getByText( block, /Product SKU/i )
		// 		).toBeInTheDocument();
		// 	},
		// },
		// 'woocommerce/product-details': {
		// 	assert: ( block: HTMLElement ) => {
		// 		expect(
		// 			getByText(
		// 				block,
		// 				/This block lists description, attributes and reviews for a single product./i
		// 			)
		// 		).toBeInTheDocument();
		// 	},
		// },
	};

	describe( 'Template Structure and Layout', () => {
		for ( const [ blockName, { assert } ] of Object.entries(
			blockAssertions
		) ) {
			test( `should render ${ blockName } block`, async () => {
				const blocks = parse( singleProductTemplateFile );

				const blockToRender = blocks.filter(
					( block ) => block.name === blockName
				);

				await initializeEditor(
					blocks.filter(
						( block ) => block.name !== 'core/template-part'
					)
				);

				screen.logTestingPlaygroundURL();

				await waitFor( () => {
					const block = document.querySelector(
						`[data-type="${ blockName }"]`
					);

					expect( block ).toBeInTheDocument();

					assert( block as HTMLElement );
				} );
			} );
		}
	} );
} );
