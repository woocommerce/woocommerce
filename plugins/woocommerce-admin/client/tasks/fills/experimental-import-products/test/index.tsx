/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import userEvent from '@testing-library/user-event';
/**
 * Internal dependencies
 */
import { Products } from '../';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

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
				name:
					'FROM A CSV FILE Import all products at once by uploading a CSV file.',
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

	test( 'should fire "tasklist_add_product" event when the cart2cart option clicked', async () => {
		const { getByRole } = render( <Products /> );

		userEvent.click(
			getByRole( 'menuitem', {
				name:
					'FROM CART2CART Migrate all store data like products, customers, and orders in no time with this 3rd party plugin. Learn more (opens in a new tab)',
			} )
		);
		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'tasklist_add_product',
				{
					method: 'migrate',
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
				name:
					'FROM A CSV FILE Import all products at once by uploading a CSV file.',
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
} );
