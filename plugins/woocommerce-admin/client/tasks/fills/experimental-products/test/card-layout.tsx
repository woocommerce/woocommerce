/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import CardLayout from '../card-layout';
import { productTypes } from '../constants';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
describe( 'CardLayout', () => {
	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
	} );
	it( 'should render all products types in CardLayout', () => {
		const { queryByText, queryAllByRole } = render(
			<CardLayout
				items={ [
					{
						...productTypes[ 0 ],
						onClick: () => {},
					},
				] }
			/>
		);

		expect( queryByText( productTypes[ 0 ].title ) ).toBeInTheDocument();

		expect( queryAllByRole( 'link' ) ).toHaveLength( 1 );
	} );

	it( 'start blank link should fire the tasklist_add_product and completion events', () => {
		const { getByText } = render(
			<CardLayout
				items={ [
					{
						...productTypes[ 0 ],
						onClick: () => {},
					},
				] }
			/>
		);
		fireEvent.click( getByText( 'Start blank' ) );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'tasklist_add_product',
			{ method: 'manually' }
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'task_completion_time',
			{ task_name: 'products', time: '0-2s' }
		);
	} );
} );
