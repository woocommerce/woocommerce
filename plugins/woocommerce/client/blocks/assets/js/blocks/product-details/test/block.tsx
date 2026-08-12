/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, waitFor, within } from '@testing-library/react';
import { createBlock, getBlockType, serialize } from '@wordpress/blocks';
import type { BlockAttributes } from '@wordpress/blocks';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import '../';
import '../../accordion/accordion-group';
import '../../accordion/inner-blocks/accordion-header';
import '../../accordion/inner-blocks/accordion-item';
import '../../accordion/inner-blocks/accordion-panel';
import '../../product-description';
import '../../product-reviews';
import '../../product-specifications';
import '../../single-product';

import { initializeEditor } from '../../../../../tests/integration/helpers/integration-test-editor';
import {
	productWithoutSpecifications,
	productWithSpecifications,
} from '../fixture';

async function setupWithSingleProduct(
	attributes: BlockAttributes,
	productId: number
) {
	const productDetailsBlock = createBlock(
		'woocommerce/product-details',
		attributes
	);

	const singleProductBlock = [
		{
			name: 'woocommerce/single-product',
			attributes: {
				productId,
			},
			innerBlocks: [ productDetailsBlock ],
		},
	];

	return initializeEditor( singleProductBlock );
}

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	isWpVersion: jest.fn().mockReturnValue( false ),
} ) );

describe( 'Product Details block', () => {
	test( 'registers only the approved style supports', () => {
		expect(
			getBlockType( 'woocommerce/product-details' )?.supports
		).toEqual( {
			interactivity: { clientNavigation: true },
			align: [ 'wide', 'full' ],
			color: { background: true, text: false },
			spacing: { margin: true, padding: true },
			__experimentalBorder: {
				color: true,
				radius: true,
				style: true,
				width: true,
			},
		} );
	} );

	test( 'preserves unstyled serialization', () => {
		expect(
			serialize( createBlock( 'woocommerce/product-details' ) )
		).toBe(
			'<!-- wp:woocommerce/product-details -->\n<div class="wp-block-woocommerce-product-details alignwide"></div>\n<!-- /wp:woocommerce/product-details -->'
		);
	} );

	test( 'serializes preset and custom styles without text color', () => {
		const content = serialize(
			createBlock( 'woocommerce/product-details', {
				backgroundColor: 'contrast',
				borderColor: 'accent-1',
				style: {
					border: { radius: '4px', style: 'solid', width: '2px' },
					spacing: {
						margin: { top: 'var:preset|spacing|20', right: '0' },
						padding: { bottom: '1rem', left: '1rem' },
					},
				},
			} )
		);

		expect( content ).toContain(
			'has-accent-1-border-color has-contrast-background-color has-background'
		);
		expect( content ).toContain(
			'border-style:solid;border-width:2px;border-radius:4px;margin-top:var(--wp--preset--spacing--20);margin-right:0;padding-bottom:1rem;padding-left:1rem'
		);
		expect( content ).not.toContain( 'has-text-color' );
	} );

	test( 'distinguishes explicit zero styles from reset styles', () => {
		const zero = serialize(
			createBlock( 'woocommerce/product-details', {
				style: {
					border: { radius: '0', width: '0' },
					spacing: { margin: { top: '0' }, padding: { bottom: '0' } },
				},
			} )
		);
		expect( zero ).toContain(
			'style="border-width:0;border-radius:0;margin-top:0;padding-bottom:0"'
		);
		expect(
			serialize(
				createBlock( 'woocommerce/product-details', { style: {} } )
			)
		).not.toContain( 'style=' );
	} );

	describe( 'Single Product block', () => {
		const server = setupServer(
			http.get( '/wc-admin/options', ( { request } ) => {
				const url = new URL( request.url );
				const options = url.searchParams.get( 'options' );
				// Check if the request is for dimension and weight units
				if (
					options ===
					'woocommerce_dimension_unit,woocommerce_weight_unit'
				) {
					return HttpResponse.json( {
						woocommerce_dimension_unit: 'cm',
						woocommerce_weight_unit: 'kg',
					} );
				}
				// Default response for other options requests
				return HttpResponse.json( {}, { status: 200 } );
			} ),
			http.get( '/wc/store/v1/products/*', () =>
				HttpResponse.json( productWithSpecifications )
			),
			http.get( '/wc/v3/products/*', () =>
				HttpResponse.json( productWithSpecifications )
			),
			http.get( '*', () => HttpResponse.json( {} ) ),
			http.options( '*', () => HttpResponse.json( {} ) )
		);

		beforeAll( () => server.listen() );

		beforeEach( () => {
			( useSelect as jest.Mock ).mockImplementation(
				( callback, deps ) => {
					const originalUseSelect =
						jest.requireActual( '@wordpress/data' ).useSelect;
					const originalResult = originalUseSelect( callback, deps );

					if (
						originalResult &&
						typeof originalResult === 'object' &&
						! Array.isArray( originalResult )
					) {
						const result = {
							...originalResult,
							wasBlockJustInserted: true,
						};

						return result;
					}
					return originalResult;
				}
			);
		} );

		afterEach( () => {
			( useSelect as jest.Mock ).mockClear();
		} );

		afterAll( () => {
			server.close();
			jest.restoreAllMocks();
		} );

		test( 'should render product specifications when product is selected', async () => {
			await setupWithSingleProduct( {}, 2 );

			await waitFor( () => {
				expect(
					screen.getByRole( 'button', { name: /description/i } )
				).toBeVisible();
				expect(
					screen.getByRole( 'button', { name: /reviews/i } )
				).toBeVisible();
				expect(
					screen.getByRole( 'button', {
						name: /additional information/i,
					} )
				).toBeVisible();
			} );

			const table = await screen.findByRole( 'table', { hidden: true } );

			expect( within( table ).getByText( /Weight/i ) ).toBeVisible();
			expect( within( table ).getByText( /150 kg/i ) ).toBeVisible();

			expect( within( table ).getByText( /Dimensions/i ) ).toBeVisible();
			expect(
				within( table ).getByText( /14 × 5.5 × 3.5 cm/i )
			).toBeVisible();

			expect( within( table ).getByText( /Material/i ) ).toBeVisible();
			expect(
				within( table ).getByText( /Acetate, Metal/i )
			).toBeVisible();
			expect( within( table ).getByText( /Size/i ) ).toBeVisible();
			expect(
				within( table ).getByText( /Medium, Large/i )
			).toBeVisible();
		} );

		test( 'should auto-remove block when product has no specifications', async () => {
			server.resetHandlers();
			server.use(
				http.get( '/wc/store/v1/products/*', () =>
					HttpResponse.json( productWithoutSpecifications )
				),
				http.get( '/wc/v3/products/*', () =>
					HttpResponse.json( productWithoutSpecifications )
				)
			);
			await setupWithSingleProduct( {}, 1 );

			await waitFor( () => {
				expect(
					screen.getByRole( 'button', { name: /description/i } )
				).toBeVisible();
				expect(
					screen.getByRole( 'button', { name: /reviews/i } )
				).toBeVisible();
				expect(
					screen.queryByRole( 'button', {
						name: /additional information/i,
					} )
				).not.toBeInTheDocument();
			} );

			expect(
				screen.queryByRole( 'table', { hidden: true } )
			).not.toBeInTheDocument();
		} );
	} );
} );
