/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

/**
 * Internal dependencies
 */
import Footer from '../footer';

describe( 'Footer', () => {
	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
	} );
	it( 'should render footer with two links', () => {
		const { queryAllByRole } = render( <Footer /> );
		expect( queryAllByRole( 'link' ) ).toHaveLength( 2 );
	} );

	it( 'clicking on import CSV should fire event tasklist_add_product with method:import and task_completion_time', () => {
		const { getByText } = render( <Footer /> );
		userEvent.click( getByText( 'Import your products from a CSV file' ) );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'tasklist_add_product',
			{ method: 'import' }
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'task_completion_time',
			{ task_name: 'products', time: '0-2s' }
		);
	} );
} );
