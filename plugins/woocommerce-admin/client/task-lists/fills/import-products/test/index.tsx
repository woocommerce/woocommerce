/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import userEvent from '@testing-library/user-event';
/**
 * Internal dependencies
 */
import { Products } from '..';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

global.fetch = jest.fn().mockImplementation( () =>
	Promise.resolve( {
		json: () => Promise.resolve( {} ),
		status: 200,
	} )
);

const confirmModalText =
	"We'll import images from Woo.com to set up your sample products.";

describe( 'Products', () => {
	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
	} );

	test( 'should fire "tasklist_add_product_from_scratch_click" event when the button clicked', async () => {
		const { getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'button', { name: 'Or add your products from scratch' } )
		);
		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'tasklist_add_product_from_scratch_click'
			)
		);
	} );

	test( 'should fire "tasklist_add_product" event when the csv option clicked', async () => {
		const { getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'menuitem', {
				name: 'FROM A CSV FILE Import all products at once by uploading a CSV file.',
			} )
		);
		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'tasklist_add_product',
				{
					method: 'import',
				}
			)
		);
	} );

	test( 'should fire "task_completion_time" event when an option clicked', async () => {
		Object.defineProperty( window, 'performance', {
			value: {
				now: jest
					.fn()
					.mockReturnValueOnce( 0 )
					.mockReturnValueOnce( 1000 )
					.mockReturnValueOnce( 0 )
					.mockReturnValueOnce( 1000 ),
			},
		} );
		const { getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'menuitem', {
				name: 'FROM A CSV FILE Import all products at once by uploading a CSV file.',
			} )
		);
		await waitFor( () => {
			expect( recordEvent ).toHaveBeenNthCalledWith(
				1,
				'tasklist_add_product',
				{ method: 'import' }
			);

			expect( recordEvent ).toHaveBeenNthCalledWith(
				2,
				'task_completion_time',
				{
					task_name: 'products',
					time: '0-2s',
				}
			);
		} );
	} );

	it( 'should send a request to load sample products when the "Import sample products" button is clicked', async () => {
		const fetchMock = jest.spyOn( global, 'fetch' );
		const { queryByText, getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'button', { name: 'Or add your products from scratch' } )
		);
		expect( queryByText( 'Load Sample Products' ) ).toBeInTheDocument();

		userEvent.click(
			getByRole( 'link', { name: 'Load Sample Products' } )
		);
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).toBeInTheDocument()
		);

		userEvent.click(
			getByRole( 'button', { name: 'Import sample products' } )
		);
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).not.toBeInTheDocument()
		);

		expect( fetchMock ).toHaveBeenCalledWith(
			'/wc-admin/onboarding/tasks/import_sample_products?_locale=user',
			{
				body: undefined,
				credentials: 'include',
				headers: { Accept: 'application/json, */*;q=0.1' },
				method: 'POST',
			}
		);
	} );

	it( 'should close the confirmation modal when the cancel button is clicked', async () => {
		const { queryByText, getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'button', { name: 'Or add your products from scratch' } )
		);
		expect( queryByText( 'Load Sample Products' ) ).toBeInTheDocument();

		userEvent.click(
			getByRole( 'link', { name: 'Load Sample Products' } )
		);
		await waitFor( () =>
			expect( queryByText( confirmModalText ) ).toBeInTheDocument()
		);

		userEvent.click( getByRole( 'button', { name: 'Cancel' } ) );
		expect( queryByText( confirmModalText ) ).not.toBeInTheDocument();
		expect( recordEvent ).toHaveBeenCalledWith(
			'tasklist_cancel_load_sample_products_click'
		);
	} );
} );
