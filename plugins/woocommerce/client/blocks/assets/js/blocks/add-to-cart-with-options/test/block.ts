/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';

/**
 * Internal dependencies
 */
import { initializeEditor } from '../../../../../tests/integration/helpers/integration-test-editor';
import '../';

const server = setupServer();

// Start MSW.
beforeAll( () => server.listen() );
afterAll( () => server.close() );

async function setup() {
	const addToCartWithOptionsBlock = [
		{
			name: 'woocommerce/add-to-cart-with-options',
		},
	];
	return await initializeEditor( addToCartWithOptionsBlock );
}

const expectHasBlock = async ( blockName: string ) => {
	const block = await screen.findAllByLabelText( `Block: ${ blockName }` );
	expect( block.length ).toBeGreaterThan( 0 );
};

describe( 'Add to Cart + Options block', () => {
	it( 'should render the placeholder when viewed as a user without permissions to edit template parts', async () => {
		server.use(
			// @todo When updating the `@wordpress/data` package to 6.7 or later,
			// this request will need to be updated to match the path in production:
			// `/wp/v2/template-parts/woocommerce/woocommerce//<template-part-slug>`.
			http.options( '/wp/v2/[object%20Object]', () => {
				return HttpResponse.json(
					{},
					{
						headers: {
							allow: 'GET',
						},
					}
				);
			} )
		);

		await setup();
		await expectHasBlock( 'Add to Cart + Options (Beta)' );

		await waitFor( () =>
			expect(
				screen.getByLabelText( 'Add to Cart + Options form' )
			).toBeInTheDocument()
		);
	} );
} );
